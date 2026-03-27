<?php

use App\Http\Middleware\Auth\CheckIfAdmin;
use App\Http\Middleware\Auth\CheckIfAdminOrSelf;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Trust reverse proxies so HTTPS/cookies/CSRF behave correctly in production.
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'admin' => CheckIfAdmin::class,
            'admin_or_self' => CheckIfAdminOrSelf::class
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
