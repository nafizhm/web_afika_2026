<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            \Illuminate\Support\Facades\Route::group([], base_path('routes/webhooks.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();


// Shared hosting may serve public files from a separate domain directory.
// DOCUMENT_ROOT is supplied by the web server, not by an HTTP header.
$documentRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
if (PHP_SAPI !== 'cli' && $documentRoot !== '') {
    $resolvedPublic = realpath($documentRoot);
    if ($resolvedPublic !== false && is_file($resolvedPublic.'/index.php')) {
        $app->usePublicPath($resolvedPublic);
    }
}

return $app;
