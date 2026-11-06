<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Demo reset
    |--------------------------------------------------------------------------
    |
    | The demo:reset command wipes member, loan, savings, share, dividend and
    | ledger data, the products and the member portal users. It refuses to run
    | unless it is switched on here AND the application runs in one of the
    | allowed environments, so it can never fire on a live system by accident.
    |
    | Set DEMO_RESET_ENABLED=true (and APP_ENV=demo or local) on the demo copy only.
    */
    'reset_enabled' => env('DEMO_RESET_ENABLED', false),

    'reset_time' => env('DEMO_RESET_TIME', '03:00'),

    'allowed_environments' => ['local', 'testing', 'demo'],
];
