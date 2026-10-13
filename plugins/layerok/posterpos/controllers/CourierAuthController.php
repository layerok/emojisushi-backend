<?php

namespace Layerok\PosterPos\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Layerok\PosterPos\Classes\Couriers\AuthenticateCourier;
use Layerok\PosterPos\Classes\Couriers\CourierTokens;
use Layerok\PosterPos\Models\Courier;

/**
 * Courier app sign-in: /api/courier/auth/login, /auth/logout and /me.
 * See docs/courier-app.md §6.
 */
class CourierAuthController
{
    /**
     * Failed logins allowed per login and IP before a pause.
     */
    private const MAX_ATTEMPTS = 5;
    private const LOCKOUT_SECONDS = 60;

    public function login(Request $request): JsonResponse
    {
        // A login that sets the cookie must come from our own page, or another
        // site could quietly sign a courier into an account of its choosing.
        if ($request->input('token_mode') !== 'bearer' && !AuthenticateCourier::fromOwnPage($request)) {
            return response()->json(['error' => 'Запрос отклонён.'], 403);
        }

        $login = trim((string) $request->input('login'));
        $password = (string) $request->input('password');

        if ($login === '' || $password === '') {
            return response()->json([
                'error' => 'Введите логин и пароль.',
                'errors' => array_filter([
                    'login' => $login === '' ? ['Введите логин.'] : null,
                    'password' => $password === '' ? ['Введите пароль.'] : null,
                ]),
            ], 422);
        }

        $key = 'courier-login:' . mb_strtolower($login) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            return response()->json([
                'error' => 'Слишком много попыток. Попробуйте через ' . RateLimiter::availableIn($key) . ' с.',
            ], 429);
        }

        $courier = Courier::where('login', $login)->first();

        // The same answer whether the login or the password was wrong, so the
        // endpoint cannot be used to find out which logins exist.
        if (!$courier || !$courier->checkPassword($password)) {
            RateLimiter::hit($key, self::LOCKOUT_SECONDS);

            return response()->json(['error' => 'Неверный логин или пароль.'], 401);
        }

        RateLimiter::clear($key);

        $session = (new CourierTokens())->issue($courier, $request->input('device_name'));
        $expiresAt = $session['model']->expires_at;

        $body = [
            'expires_at' => $expiresAt->toIso8601String(),
            'courier' => $this->present($courier),
        ];

        // A client that isn't the web app (a script, a future native app) asks
        // for the token itself. The web app never sees it: it lives in an
        // HttpOnly cookie that page scripts can't read.
        if ($request->input('token_mode') === 'bearer') {
            return response()->json($body + ['token' => $session['token']]);
        }

        return response()->json($body)
            ->withCookie(CourierTokens::cookie($request, $session['token'], $expiresAt));
    }

    public function logout(Request $request)
    {
        (new CourierTokens())->revoke($request->attributes->get('courier_token'));

        return response()->noContent()->withCookie(CourierTokens::forgetCookie($request));
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'courier' => $this->present($request->attributes->get('courier')),
        ]);
    }

    private function present(Courier $courier): array
    {
        return [
            'id' => $courier->id,
            'name' => $courier->name,
            'login' => $courier->login,
            'phone' => $courier->phone,
            // Without a Poster employee the courier has no orders; the app
            // tells them so instead of showing an empty list.
            'linked_to_poster' => (bool) $courier->poster_user_id,
        ];
    }
}
