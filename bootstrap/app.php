<?php

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
        // Alias de middleware
        $middleware->alias([
            'permiso' => \App\Http\Middleware\PermisoMiddleware::class,
        ]);

        // (Opcional) aquí mismo puedes definir grupos web/api si lo necesitas:
        // $middleware->group('web', [ ... ]);
        // $middleware->group('api', [ ... ]);
    })
    ->withExceptions(function ($exceptions) {
        //
    })
    ->create();

