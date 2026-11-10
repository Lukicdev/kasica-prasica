<?php

use App\Mcp\Servers\KasicaServer;
use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Facades\Mcp;
use Laravel\Passport\Http\Middleware\CheckToken;

Route::middleware('throttle:10,1')->group(function () {
    Mcp::oauthRoutes();
});

Mcp::web('/mcp', KasicaServer::class)
    ->middleware(['auth:api', CheckToken::using('mcp:use'), 'verified', 'throttle:60,1']);
