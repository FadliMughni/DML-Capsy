<?php

use App\Exceptions\InvalidStatusTransitionException;
use App\Http\Middleware\EnsureUserHasRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
        ]);

        // Satu-satunya halaman login di aplikasi saat ini adalah login backoffice.
        $middleware->redirectGuestsTo(fn () => route('backoffice.login'));

        // Pengguna yang sudah login dan membuka /backoffice/login diarahkan ke dashboard-nya.
        $middleware->redirectUsersTo(fn () => route('backoffice.home'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Perubahan status yang tidak valid → kembali ke halaman sebelumnya dengan pesan error.
        $exceptions->render(function (InvalidStatusTransitionException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->with('error', $e->getMessage());
        });
    })->create();
