<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Listing;
use App\Models\VehicleBrand;
use App\Rules\BulgarianPhoneLocal;
use App\Services\ImageProcessor;
use App\Support\PhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function __construct(
        private ImageProcessor $imageProcessor,
    ) {}

    public function show(Company $company): View
    {
        $brandIds = Listing::query()
            ->published()
            ->where('company_id', $company->id)
            ->distinct()
            ->pluck('brand_id');

        $brands = VehicleBrand::query()
            ->whereIn('id', $brandIds)
            ->orderBy('name')
            ->get();

        $listingsQuery = Listing::query()
            ->published()
            ->where('company_id', $company->id)
            ->with(['brand', 'model.parent', 'images', 'region', 'features', 'company'])
            ->when(request()->filled('ad_number'), fn ($q) => $q->where('ad_number', request()->integer('ad_number')))
            ->when(request()->filled('brand_id'), fn ($q) => $q->where('brand_id', request()->integer('brand_id')));

        match (request()->input('sort', 'newest')) {
            'price_asc' => $listingsQuery->orderBy('price'),
            'price_desc' => $listingsQuery->orderByDesc('price'),
            'year_desc' => $listingsQuery->orderByDesc('year'),
            'mileage_asc' => $listingsQuery->orderBy('mileage'),
            default => $listingsQuery->orderByDesc('published_at'),
        };

        $listings = $listingsQuery->paginate(12)->withQueryString();

        $company->load('region');

        $favoritedIds = auth()->check()
            ? auth()->user()->favorites()->pluck('listing_id')->all()
            : [];

        $viewMode = request()->input('view', 'grid') === 'list' ? 'list' : 'grid';

        return view('company.show', compact('company', 'listings', 'favoritedIds', 'brands', 'viewMode'));
    }

    public function edit(): View
    {
        $company = auth()->user()->company;
        abort_unless($company, 403);

        return view('company.edit', [
            'company' => $company,
            'regions' => \App\Models\Region::query()->orderBy('sort_order')->get(),
            'latestImport' => $company->mobileBgImportRuns()->first(),
        ]);
    }

    public function update(Request $request)
    {
        $company = auth()->user()->company;
        abort_unless($company, 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'phone' => ['required', 'string', new BulgarianPhoneLocal],
            'email' => ['nullable', 'email'],
            'website' => ['nullable', 'url'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string'],
            'region_id' => ['nullable', 'exists:regions,id'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'cover_image' => ['nullable', 'image', 'max:4096'],
            'remove_logo' => ['boolean'],
            'remove_cover' => ['boolean'],
        ]);

        if ($request->boolean('remove_logo') && $company->logo) {
            Storage::disk('public')->delete($company->logo);
            $data['logo'] = null;
        }

        if ($request->boolean('remove_cover') && $company->cover_image) {
            Storage::disk('public')->delete($company->cover_image);
            $data['cover_image'] = null;
        }

        if ($request->hasFile('logo')) {
            if ($company->logo) {
                Storage::disk('public')->delete($company->logo);
            }
            $data['logo'] = $this->imageProcessor->processSingleUpload(
                $request->file('logo'),
                "companies/{$company->id}",
                config('images.company_logo'),
            );
        }

        if ($request->hasFile('cover_image')) {
            if ($company->cover_image) {
                Storage::disk('public')->delete($company->cover_image);
            }
            $data['cover_image'] = $this->imageProcessor->processSingleUpload(
                $request->file('cover_image'),
                "companies/{$company->id}",
                config('images.company_cover'),
            );
        }

        unset($data['remove_logo'], $data['remove_cover']);

        $data['phone'] = PhoneNumber::fromLocalPart($data['phone']);

        $company->update($data);

        return back()->with('success', __('messages.company_updated'));
    }
}