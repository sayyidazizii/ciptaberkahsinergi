<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function(){
            Route::prefix('api/sandbox')
            ->middleware(['api','sandbox','log-request'])
            ->group(base_path('routes/api.sandbox.php'));
        }
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append(\App\Http\Middleware\ShowDebugbarForAdmin::class);
        $middleware->appendToGroup('web', [
            \App\Http\Middleware\ShowDebugbarForAdmin::class,
        ]);
        $middleware->alias([
            'log-request' => \App\Http\Middleware\LogRequest::class,
            'sandbox' => \App\Http\Middleware\MakeRequestSandbox::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
