<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ImportMobileBgListings;
use App\Models\Company;
use App\Models\CompanyApiKey;
use App\Models\MobileBgImportRun;
use App\Rules\BulgarianPhoneLocal;
use App\Services\MobileBg\MobileBgClient;
use App\Services\MobileBg\MobileBgProfileService;
use App\Support\ManagedQueue;
use App\Support\PhoneNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function index(Request $request): View
    {
        $query = Company::query()
            ->with(['user', 'region'])
            ->withCount([
                'listings',
                'apiKeys',
            ]);

        if ($search = $request->string('q')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        if ($request->string('verified')->toString() === '1') {
            $query->where('is_verified', true)->whereNotNull('verified_at');
        } elseif ($request->string('verified')->toString() === '0') {
            $query->where(function ($q) {
                $q->where('is_verified', false)->orWhereNull('verified_at');
            });
        }

        $companies = $query->latest()->paginate(20)->withQueryString();

        return view('admin.companies.index', compact('companies'));
    }

    public function show(Company $company): View
    {
        $company->load(['user', 'region'])
            ->loadCount(['listings', 'apiKeys', 'mobileBgImportRuns']);

        $apiKeys = $company->apiKeys()->latest()->get();
        $activeApiKey = $apiKeys->firstWhere('is_active', true);
        $recentImports = $company->mobileBgImportRuns()->limit(5)->get();
        $latestImport = MobileBgImportRun::latestForCompany($company);
        $recentListings = $company->listings()
            ->with(['brand', 'model.parent'])
            ->latest('updated_at')
            ->limit(10)
            ->get();

        return view('admin.companies.show', compact(
            'company',
            'apiKeys',
            'activeApiKey',
            'recentImports',
            'latestImport',
            'recentListings',
        ));
    }

    public function update(Request $request, Company $company): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', new BulgarianPhoneLocal],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        $company->update([
            'name' => $data['name'],
            'phone' => PhoneNumber::fromLocalPart($data['phone']),
            'email' => $data['email'] ?? null,
        ]);

        return back()->with('success', __('admin.company_profile_updated'));
    }

    public function updateVerification(Request $request, Company $company): RedirectResponse
    {
        $data = $request->validate([
            'is_verified' => ['required', 'boolean'],
        ]);

        $verified = $request->boolean('is_verified');

        $company->update([
            'is_verified' => $verified,
            'verified_at' => $verified ? ($company->verified_at ?? now()) : null,
        ]);

        $message = $verified
            ? __('admin.company_verified')
            : __('admin.company_unverified');

        return back()->with('success', $message);
    }

    public function generateApiKey(Company $company): RedirectResponse
    {
        if ($company->apiKeys()->where('is_active', true)->exists()) {
            return back()->with('error', __('messages.api_key_already_exists'));
        }

        $result = CompanyApiKey::generate(__('messages.api_key_default_name'), $company);

        return back()->with([
            'success' => __('admin.api_key_granted'),
            'new_api_key' => $result['plain_key'],
        ]);
    }

    public function extractMobileProfile(Request $request, Company $company, MobileBgProfileService $service): RedirectResponse
    {
        $data = $request->validate([
            'mobile_bg_profile_url' => ['required', 'string', 'max:255'],
        ]);

        try {
            $service->extractAndApply($company, $data['mobile_bg_profile_url']);
        } catch (\InvalidArgumentException $exception) {
            return back()
                ->withInput()
                ->with('error', $exception->getMessage());
        } catch (\Throwable $exception) {
            report($exception);

            $message = config('app.debug')
                ? $exception->getMessage()
                : __('messages.mobile_bg_profile_extract_failed');

            return back()
                ->withInput()
                ->with('error', $message);
        }

        return back()->with('success', __('admin.mobile_profile_extracted'));
    }

    public function importMobileListings(Request $request, Company $company, MobileBgClient $client): RedirectResponse
    {
        $data = $request->validate([
            'mobile_bg_url' => ['required', 'string', 'max:255'],
            'sync_images' => ['boolean'],
        ]);

        $sourceUrl = $client->normalizeDealerUrl($data['mobile_bg_url']);

        $activeRun = MobileBgImportRun::query()
            ->where('company_id', $company->id)
            ->whereIn('status', [MobileBgImportRun::STATUS_PENDING, MobileBgImportRun::STATUS_RUNNING])
            ->exists();

        if ($activeRun) {
            return back()->with('error', __('messages.mobile_bg_import_running'));
        }

        $run = MobileBgImportRun::query()->create([
            'company_id' => $company->id,
            'source_url' => $sourceUrl,
            'status' => MobileBgImportRun::STATUS_PENDING,
        ]);

        $company->update(['mobile_bg_url' => $sourceUrl]);

        $this->dispatchImport($run, $request->boolean('sync_images', true));

        $run->refresh();
        $message = config('queue.default') === 'sync' || $run->isFinished()
            ? __('messages.mobile_bg_import_started_sync')
            : __('messages.mobile_bg_import_started');

        return back()->with('success', $message);
    }

    public function importStatus(Company $company, MobileBgImportRun $run): JsonResponse
    {
        abort_unless($run->company_id === $company->id, 404);

        return response()->json([
            'id' => $run->id,
            'status' => $run->status,
            'total_found' => $run->total_found,
            'created_count' => $run->created_count,
            'updated_count' => $run->updated_count,
            'failed_count' => $run->failed_count,
            'errors' => $run->errors,
            'started_at' => $run->started_at?->toIso8601String(),
            'completed_at' => $run->completed_at?->toIso8601String(),
            'finished' => $run->isFinished(),
        ]);
    }

    private function dispatchImport(MobileBgImportRun $run, bool $syncImages): void
    {
        ManagedQueue::dispatch(new ImportMobileBgListings($run, $syncImages));
    }
}