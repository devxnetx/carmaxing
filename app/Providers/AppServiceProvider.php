<?php

namespace App\Providers;

use App\Support\CloudConfiguration;
use App\View\Composers\FooterComposer;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Apple\Provider as AppleProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        CloudConfiguration::apply($this->app);
    }

    public function boot(): void
    {
        if ($this->app->environment('local') && $devTo = config('mail.dev_to')) {
            Mail::alwaysTo($devTo);
        }

        RateLimiter::for('company-api', function (Request $request) {
            $key = $request->attributes->get('api_key')?->id ?? $request->ip();

            return Limit::perMinute(config('api.requests_per_minute', 60))->by('api:'.$key);
        });

        View::composer('components.footer', FooterComposer::class);

        $this->app['events']->listen(function (SocialiteWasCalled $event) {
            $event->extendSocialite('apple', AppleProvider::class);
        });
    }
}