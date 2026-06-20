<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PlatformSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(
        private PlatformSettings $settings,
    ) {}

    public function index(): View
    {
        return view('admin.settings.index', [
            'tendersEnabled' => $this->settings->tendersEnabled(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tenders_enabled' => ['nullable', 'boolean'],
        ]);

        $this->settings->setTendersEnabled($request->boolean('tenders_enabled'));

        return redirect()
            ->route('admin.settings.index')
            ->with('success', __('admin.settings_saved'));
    }
}