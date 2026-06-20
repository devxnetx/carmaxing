<?php

namespace App\Http\Controllers;

use App\Enums\AccountType;
use App\Models\Company;
use App\Rules\BulgarianPhoneLocal;
use App\Support\PhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    public function show(): View|RedirectResponse
    {
        if (! auth()->user()->needsOnboarding()) {
            return redirect()->route('dashboard');
        }

        return view('onboarding.wizard');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'account_type' => ['required', 'in:private,company'],
            'phone' => ['required_if:account_type,private', 'nullable', 'string', new BulgarianPhoneLocal],
            'company_name' => ['required_if:account_type,company', 'nullable', 'string', 'max:255'],
            'company_phone' => ['required_if:account_type,company', 'nullable', 'string', new BulgarianPhoneLocal],
            'company_city' => ['nullable', 'string', 'max:100'],
            'region_id' => ['nullable', 'exists:regions,id'],
        ]);

        $user = $request->user();
        $user->account_type = AccountType::from($validated['account_type']);

        if ($user->isPrivate()) {
            $user->phone = PhoneNumber::fromLocalPart($validated['phone']);
        }

        $user->onboarding_completed_at = now();
        $user->save();

        if ($user->isCompany()) {
            $slug = Str::slug($validated['company_name']);
            $baseSlug = $slug;
            $counter = 1;

            while (Company::query()->where('slug', $slug)->exists()) {
                $slug = $baseSlug.'-'.$counter++;
            }

            Company::query()->create([
                'user_id' => $user->id,
                'name' => $validated['company_name'],
                'slug' => $slug,
                'phone' => PhoneNumber::fromLocalPart($validated['company_phone']),
                'city' => $validated['company_city'] ?? null,
                'region_id' => $validated['region_id'] ?? null,
                'member_since_year' => now()->year,
            ]);
        }

        return redirect()->route('dashboard')->with('success', __('messages.onboarding_complete'));
    }
}