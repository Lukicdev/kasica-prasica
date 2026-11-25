<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Trusted Proxies
    |--------------------------------------------------------------------------
    |
    | The addresses of the reverse proxies or load balancers in front of the
    | app, comma separated (CIDR ranges allowed). Only requests from these
    | addresses may set X-Forwarded-* headers. Never use "*" when the app
    | can be reached directly, or clients can spoof their IP and host.
    |
    */

    'proxies' => env('TRUSTED_PROXIES', '127.0.0.1,::1'),

];
