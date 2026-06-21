<?php

use App\Http\Middleware\AuthenticateCompanyApiKey;
use App\Http\Middleware\EnsureOnboardingComplete;
use App\Http\Middleware\EnsureTendersEnabled;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\SetLocale;
use Illuminate\Console\Scheduling\Schedule;
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
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('searches:notify')->hourly();

        $schedule->command('tenders:close-expired')->everyMinute();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();