<?php

namespace App\Http\Controllers\Api;

use App\Enums\ListingStatus;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Listing;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use App\Support\HtmlToPlainText;
use App\Support\LocationCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ListingApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var Company $company */
        $company = $request->attributes->get('company');

        $perPage = min($request->integer('per_page', 25), config('api.max_per_page', 50));

        $listings = Listing::query()
            ->where('company_id', $company->id)
            ->with(['brand', 'model.parent', 'features', 'images'])
            ->when($request->input('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->filled('ad_number'), fn ($q) => $q->where('ad_number', $request->integer('ad_number')))
            ->when($request->filled('external_id'), fn ($q) => $q->where('external_id', $request->input('external_id')))
            ->latest()
            ->paginate($perPage);

        return response()->json($listings);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $company = $request->attributes->get('company');

        $listing = Listing::query()
            ->where('company_id', $company->id)
            ->where(function ($q) use ($id) {
                $q->where('id', $id)
                    ->orWhere('external_id', $id)
                    ->orWhere('ad_number', is_numeric($id) ? (int) $id : -1);
            })
            ->with(['brand', 'model.parent', 'features', 'images'])
            ->firstOrFail();

        return response()->json(['data' => $listing]);
    }

    public function store(Request $request): JsonResponse
    {
        $company = $request->attributes->get('company');
        $data = $this->validateListing($request);

        if ($this->dailyListingLimitExceeded($company->id)) {
            return response()->json([
                'message' => 'Daily listing creation limit reached.',
                'limit' => config('api.listings_per_day'),
            ], 429);
        }

        if (! empty($data['external_id'])) {
            $existing = Listing::query()
                ->where('company_id', $company->id)
                ->where('external_id', $data['external_id'])
                ->first();

            if ($existing) {
                return response()->json(['message' => 'Listing with this external_id already exists. Use PUT to update.'], 409);
            }
        }

        $listing = new Listing($data);
        $listing->user_id = $company->user_id;
        $listing->company_id = $company->id;
        $listing->status = ListingStatus::Published;
        $listing->published_at = now();
        $listing->save();

        $this->syncRelations($listing, $data);

        return response()->json(['data' => $listing->load(['brand', 'model', 'features'])], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $company = $request->attributes->get('company');

        $listing = $this->findCompanyListing($company->id, $id);

        $data = $this->validateListing($request, partial: true);
        $listing->fill($data);
        $listing->save();
        $this->syncRelations($listing, $data);

        return response()->json(['data' => $listing->fresh(['brand', 'model', 'features'])]);
    }

    public function archive(Request $request, string $id): JsonResponse
    {
        $company = $request->attributes->get('company');

        $listing = $this->findCompanyListing($company->id, $id);

        $listing->archive();

        return response()->json(['data' => $listing, 'message' => 'Listing archived.']);
    }

    public function catalog(): JsonResponse
    {
        return response()->json([
            'brands' => VehicleBrand::query()->with(['models' => fn ($q) => $q->with('children')])->orderBy('name')->get(),
            'fuel_types' => ['petrol', 'diesel', 'lpg', 'cng', 'electric', 'hybrid', 'plug-in-hybrid'],
            'transmissions' => ['manual', 'automatic', 'semi-automatic'],
            'drivetrains' => ['fwd', 'rwd', 'awd', '4x4'],
            'body_types' => ['sedan', 'hatchback', 'wagon', 'suv', 'coupe', 'cabrio', 'van', 'pickup'],
        ]);
    }

    private function validateListing(Request $request, bool $partial = false): array
    {
        $rules = [
            'external_id' => ['nullable', 'string', 'max:100'],
            'brand_id' => ['exists:vehicle_brands,id'],
            'model_id' => ['exists:vehicle_models,id'],
            'car_variant' => ['nullable', 'string', 'max:120'],
            'ad_name' => ['nullable', 'string', 'max:160'],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['integer', 'min:0'],
            'price_on_request' => ['boolean'],
            'currency' => ['in:EUR,BGN,USD'],
            'year' => ['integer', 'min:1950'],
            'mileage' => ['nullable', 'integer'],
            'fuel_type' => ['nullable', 'string'],
            'engine_power_hp' => ['nullable', 'integer'],
            'transmission' => ['nullable', 'string'],
            'drivetrain' => ['nullable', 'string'],
            'body_type' => ['nullable', 'string'],
            'region_id' => ['nullable', 'exists:regions,id'],
            'city' => ['nullable', 'string', 'max:100'],
            'country_code' => ['nullable', 'string', Rule::in(LocationCatalog::abroadCountryCodes())],
            'feature_ids' => ['nullable', 'array'],
            'feature_ids.*' => ['exists:vehicle_features,id'],
        ];

        if (! $partial) {
            $rules['brand_id'][] = 'required';
            $rules['model_id'][] = 'required';
            $rules['car_variant'][] = 'nullable';
            $rules['price'][] = $request->boolean('price_on_request') ? 'nullable' : 'required';
            $rules['year'][] = 'required';
        }

        $data = $request->validate($rules);

        if ($request->boolean('price_on_request')) {
            $data['price'] = 0;
        }

        if (! empty($data['country_code'])) {
            $data['country_code'] = strtoupper($data['country_code']);
            $data['region_id'] = null;
            $data['city'] = null;
        } else {
            $data['country_code'] = null;
        }

        if (array_key_exists('description', $data)) {
            $data['description'] = HtmlToPlainText::sanitize($data['description']);
        }

        return $data;
    }

    private function syncRelations(Listing $listing, array $data): void
    {
        if (array_key_exists('feature_ids', $data)) {
            $listing->features()->sync($data['feature_ids'] ?? []);
        }
    }

    private function findCompanyListing(int $companyId, string $id): Listing
    {
        return Listing::query()
            ->where('company_id', $companyId)
            ->where(function ($q) use ($id) {
                $q->where('id', $id)
                    ->orWhere('external_id', $id)
                    ->orWhere('ad_number', is_numeric($id) ? (int) $id : -1);
            })
            ->firstOrFail();
    }

    private function dailyListingLimitExceeded(int $companyId): bool
    {
        $limit = config('api.listings_per_day', 200);
        $count = Listing::query()
            ->where('company_id', $companyId)
            ->where('created_at', '>=', now()->startOfDay())
            ->count();

        return $count >= $limit;
    }
}