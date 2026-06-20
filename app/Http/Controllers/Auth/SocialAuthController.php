<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\WelcomeMail;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\SocialAvatarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    private array $providers = ['google', 'facebook', 'apple'];

    public function __construct(
        private SocialAvatarService $avatars,
    ) {}

    public function redirect(string $provider): RedirectResponse
    {
        $this->validateProvider($provider);

        if ($redirect = request()->query('redirect')) {
            $appHost = parse_url(config('app.url'), PHP_URL_HOST);
            $redirectHost = parse_url($redirect, PHP_URL_HOST);

            if ($redirectHost === null || $redirectHost === $appHost) {
                session(['url.intended' => $redirect]);
            }
        }

        return Socialite::driver($provider)->redirect();
    }

    public function callback(string $provider): RedirectResponse
    {
        $this->validateProvider($provider);

        $socialUser = Socialite::driver($provider)->user();

        $account = SocialAccount::query()
            ->where('provider', $provider)
            ->where('provider_id', $socialUser->getId())
            ->first();

        $remoteAvatar = $socialUser->getAvatar();

        if ($account) {
            $account->update([
                'avatar' => $remoteAvatar,
                'token' => $socialUser->token,
                'refresh_token' => $socialUser->refreshToken,
            ]);

            $this->syncUserAvatar($account->user, $remoteAvatar);

            Auth::login($account->user, remember: true);

            return $this->postLoginRedirect($account->user);
        }

        $user = User::query()->where('email', $socialUser->getEmail())->first();
        $isNewUser = false;

        if (! $user) {
            $user = User::query()->create([
                'name' => $socialUser->getName() ?? $socialUser->getNickname() ?? 'User',
                'email' => $socialUser->getEmail(),
                'email_verified_at' => now(),
            ]);
            $isNewUser = true;
        }

        $user->socialAccounts()->create([
            'provider' => $provider,
            'provider_id' => $socialUser->getId(),
            'avatar' => $remoteAvatar,
            'token' => $socialUser->token,
            'refresh_token' => $socialUser->refreshToken,
        ]);

        $this->syncUserAvatar($user, $remoteAvatar);

        Auth::login($user, remember: true);

        if ($isNewUser && $user->email) {
            Mail::to($user->email)->send(new WelcomeMail($user));
        }

        return $this->postLoginRedirect($user);
    }

    public function logout(): RedirectResponse
    {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('home');
    }

    private function postLoginRedirect(User $user): RedirectResponse
    {
        if ($user->needsOnboarding()) {
            return redirect()->route('onboarding.show');
        }

        return redirect()->intended(route('dashboard'));
    }

    private function validateProvider(string $provider): void
    {
        abort_unless(in_array($provider, $this->providers, true), 404);
    }

    private function syncUserAvatar(User $user, ?string $remoteAvatar): void
    {
        $path = $this->avatars->syncForUser($user, $remoteAvatar);

        if ($path && $path !== $user->avatar) {
            $user->update(['avatar' => $path]);
        }
    }
}