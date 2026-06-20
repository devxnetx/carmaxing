<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiRequestLog;
use App\Models\CompanyApiKey;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApiKeyController extends Controller
{
    public function index(Request $request): View
    {
        $query = CompanyApiKey::query()
            ->with(['company.user'])
            ->withCount('requestLogs');

        if ($search = $request->string('q')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('key_prefix', 'like', "%{$search}%")
                    ->orWhereHas('company', fn ($cq) => $cq->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->string('active')->toString() === '1') {
            $query->where('is_active', true);
        } elseif ($request->string('active')->toString() === '0') {
            $query->where('is_active', false);
        }

        $apiKeys = $query->latest()->paginate(20)->withQueryString();

        $usageSummary = [
            'total_keys' => CompanyApiKey::query()->count(),
            'active_keys' => CompanyApiKey::query()->where('is_active', true)->count(),
            'requests_today' => ApiRequestLog::query()->where('created_at', '>=', now()->startOfDay())->count(),
            'requests_week' => ApiRequestLog::query()->where('created_at', '>=', now()->subDays(7))->count(),
        ];

        return view('admin.api-keys.index', compact('apiKeys', 'usageSummary'));
    }

    public function revoke(CompanyApiKey $apiKey): RedirectResponse
    {
        $apiKey->update(['is_active' => false]);

        return back()->with('success', __('admin.api_key_revoked'));
    }
}