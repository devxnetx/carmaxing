<?php

namespace Database\Seeders;

use App\Enums\AccountType;
use App\Enums\ListingStatus;
use App\Models\Company;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\Region;
use App\Models\User;
use App\Models\VehicleBrand;
use App\Models\VehicleFeature;
use App\Models\VehicleModel;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $sofiaRegion = Region::query()->where('slug', 'sofia-grad')->first();
        $staraZagora = Region::query()->where('slug', 'stara-zagora')->first();

        $ratolaUser = User::query()->create([
            'name' => 'Ratola Auto',
            'email' => 'office@ratola.bg',
            'phone' => '+359888123456',
            'account_type' => AccountType::Company,
            'locale' => 'bg',
            'onboarding_completed_at' => now(),
            'email_verified_at' => now(),
        ]);

        $ratola = Company::query()->create([
            'user_id' => $ratolaUser->id,
            'name' => 'Ratola',
            'slug' => 'ratola',
            'description' => 'Ratola е утвърдена автокъща в София с над 15 години опит в търговията с премиум и спортни автомобили. Предлагаме проверени автомобили с пълна документация, гаранция и възможност за лизинг. Посетете ни на бул. „България“ или разгледайте наличността онлайн.',
            'phone' => '+359888123456',
            'email' => 'office@ratola.bg',
            'website' => 'https://ratola.bg',
            'address' => 'бул. „България“ 102',
            'city' => 'София',
            'region_id' => $sofiaRegion?->id,
            'member_since_year' => 2008,
            'is_verified' => true,
            'verified_at' => now(),
            'working_hours' => [
                'mon_fri' => '09:00 – 18:00',
                'sat' => '10:00 – 14:00',
            ],
        ]);

        $dodge = VehicleBrand::query()->where('slug', 'dodge')->first();
        $challenger = $dodge
            ? VehicleModel::query()->where('brand_id', $dodge->id)->where('slug', 'challenger')->first()
            : null;

        if ($dodge && $challenger) {
            $challengerListing = Listing::query()->create([
                'user_id' => $ratolaUser->id,
                'company_id' => $ratola->id,
                'brand_id' => $dodge->id,
                'model_id' => $challenger->id,
                'car_variant' => '6.4 Shaker',
                'ad_name' => 'генерация „Даунпайп“, интериор, пълен лизинг',
                'slug' => 'dodge-challenger-6-4-shaker-demo',
                'description' => "Dodge Challenger R/T Scat Pack Shaker 6.4 HEMI V8\n\nАвтомобил в отлично състояние, внос от САЩ, пълна сервизна история.\nShaker пакет, спортна изпускателна система, Brembo спирачки, кожен салон, навигация, камера за паркиране.\n\nВъзможен лизинг и бартер. За оглед и тест драйв: +359 888 123 456",
                'status' => ListingStatus::Published,
                'price' => 52900,
                'currency' => 'EUR',
                'price_negotiable' => true,
                'year' => 2016,
                'month' => 7,
                'mileage' => 68420,
                'fuel_type' => 'petrol',
                'engine_power_hp' => 485,
                'engine_displacement_cc' => 6424,
                'transmission' => 'automatic',
                'drivetrain' => 'rwd',
                'body_type' => 'coupe',
                'color_exterior' => 'Granite Crystal Metallic',
                'color_interior' => 'Black leather / Alcantara',
                'doors' => 2,
                'seats' => 5,
                'euro_standard' => 'euro5',
                'registration_type' => 'permanent',
                'vin' => '2C3CDZFJ9GH123456',
                'region_id' => $sofiaRegion?->id,
                'city' => 'София',
                'condition' => 'used',
                'wltp_consumption' => 13.2,
                'warranty_until' => now()->addMonths(6)->toDateString(),
                'first_registration_date' => '2016-07-15',
                'has_vin' => true,
                'has_video' => true,
                'has_vr360' => false,
                'views_count' => 1847,
                'published_at' => now()->subDays(3),
            ]);

            $challengerListing->features()->sync(VehicleFeature::query()->pluck('id'));

            $this->seedImages($challengerListing, 'dodge-challenger', 12);
        }

        $mercedes = VehicleBrand::query()->where('slug', 'mercedes-benz')->first();
        $eClass = $mercedes
            ? VehicleModel::query()->where('brand_id', $mercedes->id)->where('slug', 'e-class')->first()
            : null;

        if ($mercedes && $eClass) {
            $eListing = Listing::query()->create([
                'user_id' => $ratolaUser->id,
                'company_id' => $ratola->id,
                'brand_id' => $mercedes->id,
                'model_id' => $eClass->id,
                'car_variant' => 'E 220d AMG Line 4MATIC',
                'ad_name' => 'един собственик, сервизна книжка',
                'slug' => 'mercedes-e-220d-amg-line-demo',
                'description' => 'E-Класа в AMG Line, дизелов двигател, пълна сервизна книжка, един собственик.',
                'status' => ListingStatus::Published,
                'price' => 38900,
                'currency' => 'EUR',
                'year' => 2019,
                'mileage' => 112000,
                'fuel_type' => 'diesel',
                'engine_power_hp' => 194,
                'engine_displacement_cc' => 1950,
                'transmission' => 'automatic',
                'drivetrain' => '4x4',
                'body_type' => 'sedan',
                'color_exterior' => 'Obsidian Black',
                'euro_standard' => 'euro6',
                'region_id' => $sofiaRegion?->id,
                'city' => 'София',
                'condition' => 'used',
                'published_at' => now()->subDays(2),
            ]);
            $eListing->features()->sync($this->featureIds(['4x4', 'navigation', 'leather-interior', 'led-lights', 'parking-sensors', 'adaptive-cruise']));
            $this->seedImages($eListing, 'mercedes-e-class', 6);
        }

        $bmw = VehicleBrand::query()->where('slug', 'bmw')->first();
        $series5 = $bmw?->series()->where('slug', '5-series')->first();
        $bmw530 = $series5?->children()->where('slug', 'like', '%530%')->first()
            ?? $series5?->children()->first();

        if ($bmw && $bmw530) {
            $bmwListing = Listing::query()->create([
                'user_id' => $ratolaUser->id,
                'company_id' => $ratola->id,
                'brand_id' => $bmw->id,
                'model_id' => $bmw530->id,
                'car_variant' => '530d xDrive M Sport',
                'ad_name' => 'M Sport, панорама, Harman Kardon',
                'slug' => 'bmw-530d-xdrive-m-sport-demo',
                'description' => 'M Sport пакет, хармон кардън, панорамен покрив, сервизиран в оторизиран сервиз.',
                'status' => ListingStatus::Published,
                'price' => 42500,
                'currency' => 'EUR',
                'year' => 2018,
                'mileage' => 98500,
                'fuel_type' => 'diesel',
                'engine_power_hp' => 265,
                'engine_displacement_cc' => 2993,
                'transmission' => 'automatic',
                'drivetrain' => '4x4',
                'body_type' => 'sedan',
                'color_exterior' => 'Mineral Grey',
                'euro_standard' => 'euro6',
                'region_id' => $sofiaRegion?->id,
                'city' => 'София',
                'condition' => 'used',
                'published_at' => now()->subDay(),
            ]);
            $bmwListing->features()->sync($this->featureIds(['4x4', 'panorama', 'navigation', 'leather-interior', 'adaptive-cruise', 'head-up-display']));
            $this->seedImages($bmwListing, 'bmw-5-series', 5);
        }

        $porsche = VehicleBrand::query()->where('slug', 'porsche')->first();
        $cayenne = $porsche
            ? VehicleModel::query()->where('brand_id', $porsche->id)->where('slug', 'cayenne')->first()
            : null;

        if ($porsche && $cayenne) {
            $cayenneListing = Listing::query()->create([
                'user_id' => $ratolaUser->id,
                'company_id' => $ratola->id,
                'brand_id' => $porsche->id,
                'model_id' => $cayenne->id,
                'car_variant' => '3.0 E-Hybrid',
                'ad_name' => 'PANO, BOSE, 360°, Keyless GO',
                'slug' => 'porsche-cayenne-e-hybrid-ratola-demo',
                'description' => 'Plug-in хибрид, панорама, 360° камера, адаптивно окачване, Keyless GO.',
                'status' => ListingStatus::Published,
                'price' => 0,
                'currency' => 'EUR',
                'price_on_request' => true,
                'year' => 2020,
                'mileage' => 89000,
                'fuel_type' => 'plug-in-hybrid',
                'engine_power_hp' => 462,
                'engine_displacement_cc' => 3000,
                'transmission' => 'automatic',
                'drivetrain' => '4x4',
                'body_type' => 'suv',
                'color_exterior' => 'Carrara White',
                'euro_standard' => 'euro6',
                'wltp_consumption' => 4.4,
                'battery_capacity_kwh' => 14.1,
                'region_id' => $sofiaRegion?->id,
                'city' => 'София',
                'condition' => 'used',
                'has_vin' => true,
                'published_at' => now()->subHours(6),
            ]);
            $cayenneListing->features()->sync($this->featureIds(['4x4', '360-camera', 'panorama', 'adaptive-suspension', 'keyless', 'bluetooth', 'navigation', 'leather-interior', 'led-lights', 'alloy-wheels', 'parking-sensors', 'adaptive-cruise', 'blind-spot']));
            $this->seedImages($cayenneListing, 'porsche-cayenne', 8);
        }

        $audi = VehicleBrand::query()->where('slug', 'audi')->first();
        $a6 = $audi
            ? VehicleModel::query()->where('brand_id', $audi->id)->where('slug', 'a6')->first()
            : null;

        if ($audi && $a6) {
            $a6Listing = Listing::query()->create([
                'user_id' => $ratolaUser->id,
                'company_id' => $ratola->id,
                'brand_id' => $audi->id,
                'model_id' => $a6->id,
                'car_variant' => '40 TDI quattro S line',
                'ad_name' => 'Matrix LED, CarPlay, сервизна история',
                'slug' => 'audi-a6-40-tdi-quattro-demo',
                'description' => 'S line, Matrix LED, виртуална табло, CarPlay, пълна сервизна история.',
                'status' => ListingStatus::Published,
                'price' => 36500,
                'currency' => 'EUR',
                'year' => 2017,
                'mileage' => 143000,
                'fuel_type' => 'diesel',
                'engine_power_hp' => 204,
                'engine_displacement_cc' => 1968,
                'transmission' => 'automatic',
                'drivetrain' => '4x4',
                'body_type' => 'sedan',
                'color_exterior' => 'Daytona Grey',
                'euro_standard' => 'euro6',
                'region_id' => $sofiaRegion?->id,
                'city' => 'София',
                'condition' => 'used',
                'published_at' => now()->subHours(2),
            ]);
            $a6Listing->features()->sync($this->featureIds(['4x4', 'led-lights', 'navigation', 'carplay-android', 'leather-interior', 'parking-sensors']));
            $this->seedImages($a6Listing, 'audi-a6', 4);
        }

        $companyUser = User::query()->create([
            'name' => 'iCar Demo',
            'email' => 'demo@icar.bg',
            'phone' => '+359889559669',
            'account_type' => AccountType::Company,
            'locale' => 'bg',
            'onboarding_completed_at' => now(),
            'email_verified_at' => now(),
        ]);

        Company::query()->create([
            'user_id' => $companyUser->id,
            'name' => 'iCar',
            'slug' => 'icar',
            'description' => 'Демо автокъща за тестване на платформата AutoClasi.',
            'phone' => '+359889559669',
            'email' => 'info@icar.bg',
            'website' => 'https://icar.bg',
            'city' => 'Стара Загора',
            'region_id' => $staraZagora?->id,
            'member_since_year' => 2014,
            'is_verified' => true,
            'verified_at' => now(),
        ]);

        $privateUser = User::query()->create([
            'name' => 'Иван Петров',
            'email' => 'ivan@example.bg',
            'phone' => '0888123456',
            'account_type' => AccountType::Private,
            'locale' => 'bg',
            'onboarding_completed_at' => now(),
            'email_verified_at' => now(),
        ]);

        $adminUser = User::query()->create([
            'name' => 'CARMAXING Admin',
            'email' => 'admin@carmaxing.local',
            'account_type' => AccountType::Private,
            'locale' => 'bg',
            'onboarding_completed_at' => now(),
            'email_verified_at' => now(),
        ]);

        $adminUser->assignRole(\App\Models\Role::ADMIN);

        $x3 = $bmw?->series()->where('slug', 'x3')->first();
        $x3Model = $x3?->children()->first();

        if ($bmw && $x3Model) {
            Listing::query()->create([
                'user_id' => $privateUser->id,
                'brand_id' => $bmw->id,
                'model_id' => $x3Model->id,
                'car_variant' => 'xDrive30i G01',
                'ad_name' => 'един собственик, пълна сервизна история',
                'slug' => 'bmw-x3-xdrive30i-demo',
                'description' => 'Отлично състояние, пълна сервизна история, един собственик.',
                'status' => ListingStatus::Published,
                'price' => 20500,
                'currency' => 'EUR',
                'year' => 2019,
                'mileage' => 207000,
                'fuel_type' => 'petrol',
                'engine_power_hp' => 252,
                'transmission' => 'automatic',
                'drivetrain' => '4x4',
                'body_type' => 'suv',
                'region_id' => $sofiaRegion?->id,
                'city' => 'София',
                'condition' => 'used',
                'published_at' => now()->subHours(12),
            ]);
        }
    }

    private function featureIds(array $slugs): array
    {
        return VehicleFeature::query()->whereIn('slug', $slugs)->pluck('id')->all();
    }

    private function seedImages(Listing $listing, string $seed, int $count): void
    {
        foreach (range(1, $count) as $index) {
            ListingImage::query()->create([
                'listing_id' => $listing->id,
                'path' => "https://picsum.photos/seed/{$seed}-{$index}/800/600",
                'sort_order' => $index,
                'is_primary' => $index === 1,
            ]);
        }
    }
}