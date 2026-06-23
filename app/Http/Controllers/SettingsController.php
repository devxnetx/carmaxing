<?php

namespace App\Http\Controllers;

use App\Models\CompanyApiKey;
use App\Rules\BulgarianPhoneLocal;
use App\Support\PhoneNumber;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $apiKeys = $user->isCompany()
            ? $user->company?->apiKeys()->latest()->get() ?? collect()
            : collect();

        $activeApiKey = $apiKeys->first(fn ($key) => $key->is_active);
        $hasActiveApiKey = $activeApiKey !== null;

        return view('settings.index', compact('user', 'activeApiKey', 'hasActiveApiKey'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'locale' => ['required', 'in:bg,en'],
            'phone' => ['nullable', 'string', new BulgarianPhoneLocal],
        ]);

        $user = $request->user();
        $data['phone'] = filled($data['phone'] ?? null)
            ? PhoneNumber::fromLocalPart($data['phone'])
            : null;
        $user->update($data);

        cookie()->queue('locale', $data['locale'], 60 * 24 * 365);

        return back()->with('success', __('messages.settings_saved'));
    }

    public function generateApiKey(Request $request)
    {
        $company = $request->user()->company;
        abort_unless($company, 403);

        if ($company->apiKeys()->where('is_active', true)->exists()) {
            return back()->with('error', __('messages.api_key_already_exists'));
        }

        $result = CompanyApiKey::generate(__('messages.api_key_default_name'), $company);

        return back()->with([
            'success' => __('messages.api_key_generated'),
            'new_api_key' => $result['plain_key'],
        ]);
    }

    public function revokeApiKey(CompanyApiKey $apiKey)
    {
        abort_unless($apiKey->company->user_id === auth()->id(), 403);
        $apiKey->update(['is_active' => false]);

        return back()->with('success', __('messages.api_key_revoked'));
    }
}