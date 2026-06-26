<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Auth;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn () => route('admin.login'));
        $middleware->alias([
            'admin.guard' => \App\Http\Middleware\SetAdminDefaultGuard::class,
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'permission' => \App\Http\Middleware\CheckPermission::class,
            'sales.pipeline' => \App\Http\Middleware\EnsureSalesTeamPipeline::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (InvalidArgumentException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            if ($request->isMethodSafe()) {
                return null;
            }

            return redirect()->back()->withInput()->with('error', $e->getMessage());
        });

        $exceptions->render(function (TokenMismatchException $e, Request $request) {
            if ($request->routeIs('admin.logout')) {
                Auth::guard('admin')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('admin.login')
                    ->with('info', 'Your session expired. Please sign in again.');
            }

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'Session expired. Please refresh and try again.'], 419);
            }

            return redirect()->route('admin.login')
                ->with('error', 'Your session expired. Please sign in again.');
        });

        $exceptions->render(function (AuthorizationException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => $e->getMessage() ?: 'You are not allowed to perform this action.',
                ], 403);
            }

            if ($request->isMethodSafe()) {
                return null;
            }

            return redirect()->back()->with('error', $e->getMessage() ?: 'You are not allowed to perform this action.');
        });
    })->create();
