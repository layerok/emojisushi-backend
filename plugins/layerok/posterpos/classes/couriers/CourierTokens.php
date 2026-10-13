<?php

namespace Layerok\PosterPos\Classes\Couriers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie;
use Layerok\PosterPos\Models\Courier;
use Layerok\PosterPos\Models\CourierToken;

/**
 * Bearer tokens for the courier app.
 *
 * The token handed to the device is random; the database keeps only its
 * SHA-256, so neither a database dump nor a log of queries can be replayed as a
 * login. A plain hash (not bcrypt) is enough here: the token is 40 random
 * characters, not something a person chose, and the lookup must be by hash.
 */
class CourierTokens
{
    /**
     * Sessions last this long since they were last used, so a courier who works
     * regularly stays signed in and an abandoned phone stops working.
     */
    public const TTL_DAYS = 90;

    public const COOKIE = 'courier_session';
    public const COOKIE_PATH = '/api/courier';

    /**
     * last_used_at is refreshed at most this often, so every polling request
     * does not turn into a write.
     */
    private const TOUCH_EVERY_MINUTES = 5;

    /**
     * Whether the last resolve() pushed the expiry forward, so the web app's
     * cookie can be re-sent with the new date.
     */
    public bool $refreshed = false;

    /**
     * @return array{token: string, model: CourierToken}
     */
    public function issue(Courier $courier, ?string $deviceName = null): array
    {
        $token = Str::random(40);

        $model = CourierToken::create([
            'courier_id' => $courier->id,
            'token_hash' => $this->hash($token),
            'device_name' => $deviceName ? mb_substr($deviceName, 0, 255) : null,
            'last_used_at' => Carbon::now(),
            'expires_at' => Carbon::now()->addDays(self::TTL_DAYS),
        ]);

        return ['token' => $token, 'model' => $model];
    }

    /**
     * The session behind a token, or null if it is unknown or expired.
     */
    public function resolve(string $token): ?CourierToken
    {
        if ($token === '' || strlen($token) > 255) {
            return null;
        }

        $model = CourierToken::with('courier')->where('token_hash', $this->hash($token))->first();

        if (!$model || !$model->courier) {
            return null;
        }

        if ($model->expires_at && $model->expires_at->isPast()) {
            $model->delete();

            return null;
        }

        $this->refreshed = false;

        if (!$model->last_used_at || $model->last_used_at->lt(Carbon::now()->subMinutes(self::TOUCH_EVERY_MINUTES))) {
            $model->last_used_at = Carbon::now();
            $model->expires_at = Carbon::now()->addDays(self::TTL_DAYS);
            $model->save();
            $this->refreshed = true;
        }

        return $model;
    }

    public function revoke(CourierToken $model): void
    {
        $model->delete();
    }

    /**
     * The web app keeps its token in an HttpOnly cookie that page scripts can't
     * read, so an injected script can't steal the session. It is sent only to
     * the courier API and never with a request from another site.
     */
    public static function cookie(Request $request, string $token, Carbon $expiresAt): Cookie
    {
        return new Cookie(
            self::COOKIE,
            $token,
            $expiresAt,
            self::COOKIE_PATH,
            null,
            self::isHttps($request),
            true,               // HttpOnly
            false,              // raw
            Cookie::SAMESITE_STRICT
        );
    }

    public static function forgetCookie(Request $request): Cookie
    {
        return new Cookie(
            self::COOKIE, '', 1, self::COOKIE_PATH, null,
            self::isHttps($request), true, false, Cookie::SAMESITE_STRICT
        );
    }

    /**
     * Behind nginx and ngrok the app itself sees plain HTTP, so the proxy's
     * header decides whether the cookie may carry the Secure flag.
     */
    private static function isHttps(Request $request): bool
    {
        return $request->isSecure() || strtolower((string) $request->header('X-Forwarded-Proto')) === 'https';
    }

    private function hash(string $token): string
    {
        return hash('sha256', $token);
    }
}
