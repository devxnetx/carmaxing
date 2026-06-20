<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function show(): View|RedirectResponse
    {
        if (auth()->check()) {
            $user = auth()->user();

            return redirect()->route($user->needsOnboarding() ? 'onboarding.show' : 'dashboard');
        }

        return view('auth.login');
    }
}