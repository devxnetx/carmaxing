<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function show(): View|RedirectResponse
    {
        if (auth()->check()) {
            return redirect()->to($this->homeFor(auth()->user()));
        }

        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt(['email' => $credentials['email'], 'password' => $credentials['password']])) {
            throw ValidationException::withMessages([
                'email' => __('messages.login_failed'),
            ]);
        }

        $request->session()->regenerate();

        $user = $request->user();

        if ($user->needsOnboarding()) {
            return redirect()->route('onboarding.show');
        }

        return redirect()->intended($this->homeFor($user));
    }

    private function homeFor($user): string
    {
        if ($user->needsOnboarding()) {
            return route('onboarding.show');
        }

        return $user->isAdmin()
            ? route('admin.dashboard')
            : route('dashboard');
    }
}