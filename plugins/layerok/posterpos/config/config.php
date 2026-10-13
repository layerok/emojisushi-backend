<?php

return [
    /*
     * Pages allowed to use the courier API with the courier's session cookie:
     * the courier web app, hosted on its own (courier-app/ in this repo).
     * Comma-separated in COURIER_APP_ORIGINS. The localhost entry is for running
     * the app locally; a production session cookie is never sent from there.
     */
    'courier_app_origins' => array_values(array_filter(array_map(
        fn($origin) => rtrim(trim($origin), '/'),
        explode(',', env('COURIER_APP_ORIGINS', 'https://courier.emojisushi.com.ua,http://localhost:8081'))
    ))),

    /*
     * Also accept the app served from a local-network address (10.x, 172.16–31.x,
     * 192.168.x), so it can be tried on a phone over Wi-Fi against a developer's
     * machine. On by default only when APP_ENV=local. Such an origin is never the
     * same site as api.emojisushi.com.ua, so a production session cookie would
     * not be sent from it either way.
     */
    'courier_app_allow_private_network' => (bool) env('COURIER_APP_ALLOW_LAN', env('APP_ENV') === 'local'),
];
