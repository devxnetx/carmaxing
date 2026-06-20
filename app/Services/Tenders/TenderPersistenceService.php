<?php

namespace App\Services\Tenders;

use App\Enums\TenderStatus;
use App\Models\Tender;
use App\Models\User;
use App\Services\ImageProcessor;
use App\Support\HtmlToPlainText;
use App\Support\LocationCatalog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

class TenderPersistenceService
{
    public function __construct(
        private ImageProcessor $imageProcessor,
    ) {}

    public function create(Request $request, User $user): Tender
    {
        $data = $request->validate($this->rules());

        $location = LocationCatalog::normalizeListingLocation(
            $data['location_type'],
            isset($data['region_id']) ? (int) $data['region_id'] : null,
            $data['city'] ?? null,
            null,
        );

        unset($data['location_type']);

        $durationDays = (int) $data['duration_days'];
        $startsAt = now();
        $endsAt = $startsAt->copy()->addDays($durationDays);

        $tender = Tender::query()->create([
            ...$data,
            ...$location,
            'description' => HtmlToPlainText::sanitize($data['description'] ?? null),
            'user_id' => $user->id,
            'company_id' => $user->isCompany() ? $user->company?->id : null,
            'status' => TenderStatus::Active,
            'bid_increment' => (int) $data['bid_increment'],
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
        ]);

        $this->storeImages($tender, $request->file('images', []));

        return $tender->fresh(['images', 'brand', 'model.parent', 'region', 'company']);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $maxDays = config('tenders.max_duration_days');
        $minDays = config('tenders.min_duration_days');
        $allowedIncrements = config('tenders.allowed_bid_increments', [100]);

        return [
            'brand_id' => ['required', 'exists:vehicle_brands,id'],
            'model_id' => ['required', 'exists:vehicle_models,id'],
            'car_variant' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string'],
            'year' => ['required', 'integer', 'min:1950', 'max:'.(date('Y') + 1)],
            'mileage' => ['nullable', 'integer', 'min:0'],
            'fuel_type' => ['nullable', 'string'],
            'engine_power_hp' => ['nullable', 'integer'],
            'transmission' => ['nullable', 'string'],
            'body_type' => ['nullable', 'string'],
            'color_exterior' => ['nullable', 'string'],
            'condition' => ['nullable', 'string', Rule::in(['used', 'new'])],
            'location_type' => ['required', Rule::in(['bg'])],
            'region_id' => ['required', 'exists:regions,id'],
            'city' => ['required', 'string', 'max:120'],
            'starting_price' => ['required', 'integer', 'min:1'],
            'bid_increment' => ['required', 'integer', Rule::in($allowedIncrements)],
            'minimum_price' => ['nullable', 'integer', 'min:0'],
            'duration_days' => ['required', 'integer', "min:{$minDays}", "max:{$maxDays}"],
            'images' => ['nullable', 'array', 'max:20'],
            'images.*' => ['image', 'max:10240'],
        ];
    }

    /**
     * @param  array<int, UploadedFile|null>  $files
     */
    private function storeImages(Tender $tender, array $files): void
    {
        $sortOrder = 0;
        $isFirst = true;

        foreach ($files as $file) {
            if (! $file) {
                continue;
            }

            try {
                $processed = $this->imageProcessor->processUpload(
                    $file,
                    "tenders/{$tender->id}",
                    config('images.listing'),
                );
            } catch (\Throwable) {
                continue;
            }

            $sortOrder++;

            $tender->images()->create([
                ...$processed,
                'sort_order' => $sortOrder,
                'is_primary' => $isFirst,
            ]);

            $isFirst = false;
        }
    }

    /**
     * @return array<int, array{value: int, label: string}>
     */
    public function durationOptions(): array
    {
        $min = config('tenders.min_duration_days');
        $max = config('tenders.max_duration_days');
        $options = [];

        for ($days = $min; $days <= $max; $days++) {
            $options[] = [
                'value' => $days,
                'label' => $this->durationLabel($days),
            ];
        }

        return $options;
    }

    public function durationLabel(int $days): string
    {
        if ($days % 7 === 0) {
            $weeks = (int) ($days / 7);

            return trans_choice('tenders.duration_weeks', $weeks, ['count' => $weeks]);
        }

        return trans_choice('tenders.duration_days', $days, ['count' => $days]);
    }
}