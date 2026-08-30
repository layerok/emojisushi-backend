<?php

namespace Layerok\Restapi\Services;

use Illuminate\Http\Request;
use Layerok\PosterPos\Models\SmsConfirmation;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use GuzzleHttp\Exception\RequestException;
use Layerok\PosterPos\Models\FcmToken;

class FcmService
{
    private const CONCURRENCY = 30;

    private const FLUSH_EVERY = 200;

    // IID's batchAdd/batchRemove accept at most 1000 registration tokens per call.
    private const IID_BATCH_LIMIT = 1000;

    private const INVALID_TOKEN_ERRORS = [
        'UNREGISTERED',     // app uninstalled
        'INVALID_ARGUMENT', // malformed token
        'NOT_FOUND',        // token doesn't exist
    ];

    // Fixed set of city topics. A city value that isn't in this list is stored
    // as-is on the token but doesn't get a topic subscription.
    public const CITY_TOPICS = ['odesa', 'chorno'];

    public const ALL_USERS_TOPIC = 'all_users';

    protected string $projectId;
    protected string $accessToken;
    protected Client $http;

    public function __construct()
    {
        $serviceAccount = json_decode(
            file_get_contents(storage_path('app/firebase-service-account.json')),
            true
        );

        $this->projectId = $serviceAccount['project_id'];
        $this->accessToken = $this->getAccessToken();
        $this->http = new Client();
    }

    public function sendToToken(string $token, string $title, string $body, array $data = []): bool
    {
        try {
            $this->http->post(
                "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send",
                [
                    'headers' => $this->requestHeaders(),
                    'json' => $this->buildMessage(['token' => $token], $title, $body, $data),
                ]
            );

            FcmToken::where('fcm_token', $token)
                ->update(['last_used_at' => now()]);

            return true;
        } catch (RequestException $e) {
            $errorCode = $this->classifyError($token, $e);

            if (in_array($errorCode, self::INVALID_TOKEN_ERRORS, true)) {
                FcmToken::where('fcm_token', $token)->delete();
            }

            return false;
        }
    }

    /**
     * Send to every device subscribed to a topic in a single request. Devices
     * must already be subscribed via subscribeToTopic() — registering a token
     * does not, by itself, make it reachable through a topic.
     */
    public function sendToTopic(string $topic, string $title, string $body, array $data = []): bool
    {
        try {
            $this->http->post(
                "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send",
                [
                    'headers' => $this->requestHeaders(),
                    'json' => $this->buildMessage(['topic' => $topic], $title, $body, $data),
                ]
            );

            return true;
        } catch (RequestException $e) {
            $this->classifyError("topic:{$topic}", $e);

            return false;
        }
    }

    /**
     * Subscribe tokens to a topic via the IID service. Returns an array of
     * error messages, one per failed chunk of up to 1000 tokens; empty means
     * every chunk succeeded.
     */
    public function subscribeToTopic(array $tokens, string $topic): array
    {
        return $this->modifyTopicSubscription($tokens, $topic, 'batchAdd');
    }

    /**
     * Unsubscribe tokens from a topic. Same return convention as subscribeToTopic().
     */
    public function unsubscribeFromTopic(array $tokens, string $topic): array
    {
        return $this->modifyTopicSubscription($tokens, $topic, 'batchRemove');
    }

    private function modifyTopicSubscription(array $tokens, string $topic, string $action): array
    {
        $errors = [];

        foreach (array_chunk(array_values($tokens), self::IID_BATCH_LIMIT) as $chunk) {
            try {
                $this->http->post("https://iid.googleapis.com/iid/v1:{$action}", [
                    'headers' => array_merge($this->requestHeaders(), [
                        // Required by the IID service on top of the Bearer token,
                        // which is otherwise the same firebase.messaging-scoped
                        // OAuth2 token used for messages:send. If this comes back
                        // 401, the fix is widening the scope claim in
                        // getAccessToken() to include
                        // https://www.googleapis.com/auth/cloud-platform.
                        'access_token_auth' => 'true',
                    ]),
                    'json' => [
                        'to' => "/topics/{$topic}",
                        'registration_tokens' => $chunk,
                    ],
                ]);
            } catch (RequestException $e) {
                $this->classifyError("topic:{$topic}:{$action}", $e);
                $errors[] = sprintf(
                    '%s of %d tokens to [%s]: %s',
                    $action,
                    count($chunk),
                    $topic,
                    $e->getMessage()
                );
            }
        }

        return $errors;
    }

    public function sendToAll(string $title, string $body, array $data = []): void
    {
        $tokens = FcmToken::pluck('fcm_token')->toArray();
        $this->sendToMultipleTokens($tokens, $title, $body, $data);
    }

    public function sendToMultipleTokens(array $tokens, string $title, string $body, array $data = []): void
    {
        if (empty($tokens)) {
            return;
        }

        // Removes PHP's own execution-time cap for this request; the web server's
        // read timeout (nginx/php-fpm) is a separate, outer ceiling this can't lift.
        set_time_limit(0);

        $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";
        $headers = $this->requestHeaders();

        $requests = function () use ($tokens, $title, $body, $data, $url, $headers) {
            foreach ($tokens as $token) {
                yield $token => new GuzzleRequest(
                    'POST',
                    $url,
                    $headers,
                    json_encode($this->buildMessage(['token' => $token], $title, $body, $data))
                );
            }
        };

        $sentTokens = [];
        $deadTokens = [];

        // Flushed periodically, not just after the whole pool settles, so a batch
        // killed mid-flight by the web server's read timeout still keeps whatever
        // progress it made instead of losing it all.
        $flushSent = function (bool $force = false) use (&$sentTokens) {
            if (!empty($sentTokens) && ($force || count($sentTokens) >= self::FLUSH_EVERY)) {
                FcmToken::whereIn('fcm_token', $sentTokens)->update(['last_used_at' => now()]);
                $sentTokens = [];
            }
        };
        $flushDead = function (bool $force = false) use (&$deadTokens) {
            if (!empty($deadTokens) && ($force || count($deadTokens) >= self::FLUSH_EVERY)) {
                FcmToken::whereIn('fcm_token', $deadTokens)->delete();
                $deadTokens = [];
            }
        };

        $pool = new Pool($this->http, $requests(), [
            'concurrency' => self::CONCURRENCY,
            'fulfilled' => function ($response, $token) use (&$sentTokens, $flushSent) {
                $sentTokens[] = $token;
                $flushSent();
            },
            'rejected' => function ($reason, $token) use (&$deadTokens, $flushDead) {
                $errorCode = $this->classifyError($token, $reason);

                if (in_array($errorCode, self::INVALID_TOKEN_ERRORS, true)) {
                    $deadTokens[] = $token;
                    $flushDead();
                }
            },
        ]);

        $pool->promise()->wait();

        $flushSent(force: true);
        $flushDead(force: true);
    }

    private function requestHeaders(): array
    {
        return [
            'Authorization' => "Bearer {$this->accessToken}",
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * @param array $target Either ['token' => $token] or ['topic' => $topic].
     */
    private function buildMessage(array $target, string $title, string $body, array $data = []): array
    {
        $message = $target + [
            'notification' => [
                'title' => $title,
                'body' => $body,
            ],
        ];

        if (!empty($data)) {
            $message['data'] = array_map('strval', $data);
        }

        return ['message' => $message];
    }

    /**
     * Log a failed send and return FCM's error code, if the failure carried one.
     * Covers 4xx (ClientException), 5xx (ServerException) and connection-level
     * failures (ConnectException) alike, since all extend RequestException.
     */
    private function classifyError(string $context, \Throwable $reason): ?string
    {
        if (!$reason instanceof RequestException || !$reason->hasResponse()) {
            \Log::error('FCM: request failed', [
                'context' => $context,
                'message' => $reason->getMessage(),
            ]);

            return null;
        }

        $responseBody = $reason->getResponse()->getBody()->getContents();

        \Log::error('FCM RESPONSE', [
            'context' => $context,
            'body' => $responseBody,
        ]);

        $body = json_decode($responseBody, true);
        $status  = $body['error']['status'] ?? '';
        $details = $body['error']['details'] ?? [];

        $errorCode = $status;
        foreach ($details as $detail) {
            if (isset($detail['errorCode'])) {
                $errorCode = $detail['errorCode'];
                break;
            }
        }

        if (!in_array($errorCode, self::INVALID_TOKEN_ERRORS, true)) {
            \Log::error("FCM: failed to send [{$errorCode}]", ['context' => $context]);
        }

        return $errorCode;
    }

    private function getAccessToken(): string
    {
        $serviceAccount = json_decode(
            file_get_contents(storage_path('app/firebase-service-account.json')),
            true
        );

        $now = time();

        // Build JWT header + payload
        $header  = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $payload = base64_encode(json_encode([
            'iss'   => $serviceAccount['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud'   => 'https://oauth2.googleapis.com/token',
            'iat'   => $now,
            'exp'   => $now + 3600,
        ]));

        $unsignedJwt = "{$header}.{$payload}";

        // Sign with private key
        openssl_sign($unsignedJwt, $signature, $serviceAccount['private_key'], 'SHA256');
        $jwt = "{$unsignedJwt}." . base64_encode($signature);

        // Exchange JWT for access token
        $response = file_get_contents('https://oauth2.googleapis.com/token', false, stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => 'Content-Type: application/x-www-form-urlencoded',
                'content' => http_build_query([
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion'  => $jwt,
                ]),
            ],
        ]));

        return json_decode($response, true)['access_token'];
    }
}
