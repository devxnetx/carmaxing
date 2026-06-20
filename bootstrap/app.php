<?php

use App\Http\Middleware\AuthenticateCompanyApiKey;
use App\Http\Middleware\EnsureOnboardingComplete;
use App\Http\Middleware\EnsureTendersEnabled;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo('/login');

        $middleware->redirectUsersTo(function () {
            $user = auth()->user();

            return $user && $user->needsOnboarding()
                ? route('onboarding.show', absolute: false)
                : route('dashboard', absolute: false);
        });

        $middleware->web(append: [
            SetLocale::class,
        ]);

        $middleware->alias([
            'onboarding' => EnsureOnboardingComplete::class,
            'admin' => EnsureUserIsAdmin::class,
            'company.api' => AuthenticateCompanyApiKey::class,
            'tenders.enabled' => EnsureTendersEnabled::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $forceShowErrors = filter_var(env('FORCE_SHOW_ERRORS', true), FILTER_VALIDATE_BOOLEAN);

        if ($forceShowErrors) {
            config(['app.debug' => true]);
        }

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') && ! $forceShowErrors,
        );

        $exceptions->render(function (\Throwable $e, Request $request) use ($forceShowErrors) {
            if (! $forceShowErrors) {
                return null;
            }

            config(['app.debug' => true]);

            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => collect($e->getTrace())->take(20)->all(),
                ], 500);
            }

            return null;
        });
    })->create();