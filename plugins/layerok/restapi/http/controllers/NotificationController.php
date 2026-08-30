<?php

namespace Layerok\Restapi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Layerok\PosterPos\Models\FcmToken;
use Layerok\Restapi\Services\FcmService;
use October\Rain\Exception\ValidationException;
use OFFLINE\Mall\Models\WishlistItem;
use Validator;

class NotificationController extends Controller
{
    public function register(): JsonResponse
    {
        $v = Validator::make(input('params', []), [
            'fcm_token' => 'required|string',
            'city' => 'nullable|string',
        ]);

        if ($v->fails()) {
            throw new ValidationException($v);
        }

        // Read from the validated payload, not a second raw input() call — the
        // 'nullable|string' rule is what guarantees $rawCity is a string or
        // null, which resolveCityTopic()'s (?string) type hint depends on.
        $validated = $v->validated();
        $token = $validated['fcm_token'];
        $rawCity = $validated['city'] ?? null;

        $existing = FcmToken::where('fcm_token', $token)->first();
        $isNew = !$existing;
        $previousTopic = $existing ? $this->resolveCityTopic($existing->city) : null;
        $newTopic = $this->resolveCityTopic($rawCity);

        FcmToken::updateOrCreate(
            ['fcm_token' => $token],
            [
                'platform' => input('params.platform'),
                'city' => $rawCity !== null && trim($rawCity) !== '' ? trim($rawCity) : null,
            ]
        );

        // Only touch FCM (which pays a blocking OAuth2 round-trip per instantiation)
        // when something about this token's subscriptions actually needs to change.
        if ($isNew || $previousTopic !== $newTopic) {
            $service = app(FcmService::class);

            if ($isNew) {
                $service->subscribeToTopic([$token], FcmService::ALL_USERS_TOPIC);
            }

            if ($previousTopic !== $newTopic) {
                if ($previousTopic) {
                    $service->unsubscribeFromTopic([$token], $previousTopic);
                }
                if ($newTopic) {
                    $service->subscribeToTopic([$token], $newTopic);
                }
            }
        }

        return response()->json(['success' => true]);
    }

    /**
     * Resolves a raw client-supplied city to one of FcmService::CITY_TOPICS,
     * or null if it's empty or not a recognized city. Unrecognized values are
     * still stored on the token as-is (see register()) so real client values
     * are visible for whitelist decisions, they just don't get a subscription.
     */
    private function resolveCityTopic(?string $city): ?string
    {
        $normalized = strtolower(trim((string) $city));

        if ($normalized === '') {
            return null;
        }

        if (in_array($normalized, FcmService::CITY_TOPICS, true)) {
            return $normalized;
        }

        \Log::warning('FCM: unrecognized city value, skipping city topic subscription', [
            'city' => $city,
        ]);

        return null;
    }
}
