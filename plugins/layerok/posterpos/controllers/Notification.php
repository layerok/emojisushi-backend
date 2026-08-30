<?php

namespace Layerok\PosterPos\Controllers;

use BackendMenu;
use Backend\Classes\Controller;
use Layerok\posterpos\Models\FcmToken;
use Layerok\Restapi\Services\FcmService;
use DB;

/**
 * Notification Backend Controller
 *
 * @link https://docs.octobercms.com/3.x/extend/system/controllers.html
 */
class Notification extends Controller
{

    /**
     * @var array required permissions
     */
    public $requiredPermissions = ['layerok.posterpos.notification'];

    /**
     * __construct the controller
     */
    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('Layerok.Posterpos', 'posterpos', 'notification');
    }
    public function index()
    {
        $this->vars['platformCounts'] = FcmToken::query()
            ->select('platform', DB::raw('COUNT(*) as total'))
            ->groupBy('platform')
            ->orderBy('total', 'desc')
            ->get();

        $this->vars['cityCounts'] = FcmToken::query()
            ->select('city', DB::raw('COUNT(*) as total'))
            ->groupBy('city')
            ->orderBy('total', 'desc')
            ->get();

        $this->vars['totalTokens'] = FcmToken::count();
        $this->vars['cityTopics'] = FcmService::CITY_TOPICS;
        $this->vars['allUsersTopic'] = FcmService::ALL_USERS_TOPIC;
    }
    public function onSendNotification()
    {
        $title = post('title', 'Test notification');
        $body = post('body', 'This is a test notification');

        app(FcmService::class)->sendToAll(
            $title,
            $body
        );

        \Flash::success('Notification sent.');

        return \Redirect::refresh();
    }

    /**
     * Sends via a single request to everyone currently subscribed to the given
     * topic. Requires "Subscribe existing tokens to topics" to have been run
     * at least once — otherwise the topic has no subscribers yet and this
     * silently reaches nobody despite reporting success.
     */
    public function onSendToTopic()
    {
        $topic = post('topic', FcmService::ALL_USERS_TOPIC);
        $title = post('title', 'Test notification');
        $body = post('body', 'This is a test notification');

        $sent = app(FcmService::class)->sendToTopic($topic, $title, $body);

        if ($sent) {
            \Flash::success("Notification sent to topic [{$topic}].");
        } else {
            \Flash::error("Failed to send to topic [{$topic}] — check the log for details.");
        }

        return \Redirect::refresh();
    }

    /**
     * One-time (repeatable) backfill: subscribes every existing token to
     * all_users, and to its city topic where the stored city matches one of
     * FcmService::CITY_TOPICS. Safe to run more than once — re-subscribing an
     * already-subscribed token is a no-op on FCM's side.
     */
    public function onSubscribeExisting()
    {
        $service = app(FcmService::class);
        $errors = [];

        $allTokens = FcmToken::pluck('fcm_token')->toArray();
        $errors = array_merge($errors, $service->subscribeToTopic($allTokens, FcmService::ALL_USERS_TOPIC));

        $citySummary = [];
        foreach (FcmService::CITY_TOPICS as $city) {
            $cityTokens = FcmToken::where('city', $city)->pluck('fcm_token')->toArray();

            if (empty($cityTokens)) {
                continue;
            }

            $errors = array_merge($errors, $service->subscribeToTopic($cityTokens, $city));
            $citySummary[] = count($cityTokens) . " to [{$city}]";
        }

        if (empty($errors)) {
            $message = 'Subscribed ' . count($allTokens) . ' token(s) to [' . FcmService::ALL_USERS_TOPIC . ']';
            if (!empty($citySummary)) {
                $message .= ', plus ' . implode(', ', $citySummary);
            }
            \Flash::success($message . '.');
        } else {
            \Flash::error(count($errors) . ' batch(es) failed — check the log for details. First error: ' . reset($errors));
        }

        return \Redirect::refresh();
    }
}
