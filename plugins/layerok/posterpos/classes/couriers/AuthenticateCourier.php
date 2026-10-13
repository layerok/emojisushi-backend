<?php

namespace Layerok\PosterPos\Classes\Couriers;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

/**
 * Route middleware for the courier app. The session token comes either from
 * the web app's HttpOnly cookie or, for other clients, from
 * "Authorization: Bearer <token>". The courier and the session are put on the
 * request as the 'courier' and 'courier_token' attributes.
 */
class AuthenticateCourier
{
    /**
     * Header the web app sends on every request. A plain form or link from
     * another site can't add it; a script can only after a CORS preflight, and
     * then its Origin is checked against the allow-list.
     */
    public const APP_HEADER = 'X-Courier-App';

    public function handle(Request $request, Closure $next)
    {
        $bearer = (string) $request->bearerToken();
        $token = $bearer !== '' ? $bearer : (string) $request->cookies->get(CourierTokens::COOKIE, '');

        // A browser page other than the courier app gets nothing, even a read:
        // the API's CORS lets any origin through, so the allow-list is here.
        if (!self::originAllowed($request)) {
            return response()->json(['error' => 'Запрос отклонён.'], 403);
        }

        // Plugin routes run without Laravel's CSRF protection, and the browser
        // attaches the cookie by itself, so a cookie-authenticated change must
        // prove it came from the courier app.
        if ($bearer === '' && !$request->isMethodSafe() && !self::fromOwnPage($request)) {
            return response()->json(['error' => 'Запрос отклонён.'], 403);
        }

        $tokens = new CourierTokens();
        $session = $tokens->resolve($token);

        if (!$session) {
            return response()->json(['error' => 'Сессия истекла. Войдите снова.'], 401)
                ->withCookie(CourierTokens::forgetCookie($request));
        }

        $request->attributes->set('courier', $session->courier);
        $request->attributes->set('courier_token', $session);

        $response = $next($request);

        // The session runs 90 days from its last use; the cookie has to keep up,
        // or a courier who works every day would still be signed out on day 90.
        if ($bearer === '' && $tokens->refreshed && $session->exists) {
            $response->headers->setCookie(CourierTokens::cookie($request, $token, $session->expires_at));
        }

        return $response;
    }

    /**
     * The request carries the app's header and, if a browser sent it, comes
     * from the courier app's own address.
     */
    public static function fromOwnPage(Request $request): bool
    {
        return $request->header(self::APP_HEADER) === '1' && self::originAllowed($request);
    }

    /**
     * Browsers always say which page a cross-site request comes from; a request
     * without an Origin is not from another page (scripts, same-page loads).
     */
    public static function originAllowed(Request $request): bool
    {
        $origin = rtrim((string) $request->header('Origin'), '/');

        if ($origin === '') {
            return true;
        }

        $allowed = (array) Config::get('layerok.posterpos::courier_app_origins', []);

        if (in_array($origin, $allowed, true)) {
            return true;
        }

        return Config::get('layerok.posterpos::courier_app_allow_private_network', false)
            && self::isPrivateNetwork($origin);
    }

    /**
     * http(s)://<local-network IPv4>[:port] — a developer's machine on Wi-Fi.
     */
    private static function isPrivateNetwork(string $origin): bool
    {
        $host = parse_url($origin, PHP_URL_HOST);
        $scheme = parse_url($origin, PHP_URL_SCHEME);

        return in_array($scheme, ['http', 'https'], true)
            && is_string($host)
            && filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false
            && filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE) === false
            && !str_starts_with($host, '127.');
    }
}
