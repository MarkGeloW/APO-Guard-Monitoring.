<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Break-glass emergency administrator
    |--------------------------------------------------------------------------
    |
    | Used only when normal administrator access is lost. Unlock with:
    | php artisan apo:break-glass --secret=YOUR_SECRET
    |
    */

    'break_glass' => [
        'email' => env('BREAK_GLASS_EMAIL', 'emergency@aposecurity.com'),
        'name' => env('BREAK_GLASS_NAME', 'Emergency Administrator'),
        'secret' => env('BREAK_GLASS_SECRET'),
    ],

];
