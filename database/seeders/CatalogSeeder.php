<?php

namespace Database\Seeders;

use App\Models\Region;
use App\Models\VehicleBrand;
use App\Models\VehicleFeature;
use App\Models\VehicleFeatureCategory;
use App\Models\VehicleModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedRegions();
        $this->seedFeatures();
        $this->seedBrandsAndModels();
    }

    private function seedRegions(): void
    {
        $regions = json_decode(file_get_contents(database_path('data/regions.json')), true);

        foreach ($regions as $index => $region) {
            Region::query()->create([
                ...$region,
                'sort_order' => $index + 1,
            ]);
        }
    }

    private function seedFeatures(): void
    {
        $data = json_decode(file_get_contents(database_path('data/vehicle_features.json')), true);

        foreach ($data['categories'] as $index => $category) {
            $cat = VehicleFeatureCategory::query()->create([
                ...$category,
                'sort_order' => $index + 1,
            ]);

            foreach ($data['features'][$category['slug']] ?? [] as $fIndex => $feature) {
                VehicleFeature::query()->create([
                    'category_id' => $cat->id,
                    ...$feature,
                    'sort_order' => $fIndex + 1,
                ]);
            }
        }
    }

    private function seedBrandsAndModels(): void
    {
        $brands = [
            ['name' => 'Mercedes-Benz', 'popular' => true, 'models' => ['A-Class', 'B-Class', 'C-Class', 'E-Class', 'S-Class', 'GLA', 'GLB', 'GLC', 'GLE', 'GLS', 'CLA', 'CLS', 'G-Class', 'EQE', 'EQS']],
            ['name' => 'BMW', 'popular' => true, 'series' => [
                '1 Series' => ['116', '118', '120', '125', 'M135'],
                '2 Series' => ['218', '220', '225', '230', 'M240'],
                '3 Series' => ['316', '318', '320', '330', '340', 'M3'],
                '4 Series' => ['420', '430', '440', 'M4'],
                '5 Series' => ['520', '530', '540', '550', 'M5'],
                'X1' => ['sDrive18i', 'xDrive20i', 'xDrive25i'],
                'X3' => ['xDrive20d', 'xDrive30i', 'M40i', 'M'],
                'X5' => ['xDrive30d', 'xDrive40i', 'xDrive45e', 'M50i', 'M'],
                'X6' => ['xDrive30d', 'xDrive40i', 'M50i'],
                'X7' => ['xDrive40i', 'M60i'],
            ]],
            ['name' => 'Audi', 'popular' => true, 'models' => ['A1', 'A3', 'A4', 'A5', 'A6', 'A7', 'A8', 'Q2', 'Q3', 'Q5', 'Q7', 'Q8', 'e-tron', 'TT', 'R8']],
            ['name' => 'VW', 'popular' => true, 'models' => ['Golf', 'Polo', 'Passat', 'Tiguan', 'Touareg', 'T-Roc', 'Arteon', 'ID.3', 'ID.4', 'ID.5', 'Caddy', 'Transporter']],
            ['name' => 'Toyota', 'popular' => true, 'models' => ['Yaris', 'Corolla', 'Camry', 'RAV4', 'C-HR', 'Highlander', 'Land Cruiser', 'Prius', 'Aygo', 'Supra']],
            ['name' => 'Porsche', 'popular' => true, 'models' => ['911', 'Cayenne', 'Macan', 'Panamera', 'Taycan', 'Boxster', 'Cayman', '718']],
            ['name' => 'Opel', 'popular' => true, 'models' => ['Corsa', 'Astra', 'Insignia', 'Mokka', 'Crossland', 'Grandland', 'Combo', 'Vivaro']],
            ['name' => 'Peugeot', 'popular' => true, 'models' => ['208', '308', '508', '2008', '3008', '5008', 'Partner', 'Rifter']],
            ['name' => 'Hyundai', 'popular' => false, 'models' => ['i10', 'i20', 'i30', 'Elantra', 'Tucson', 'Santa Fe', 'Kona', 'Ioniq 5', 'Ioniq 6']],
            ['name' => 'Kia', 'popular' => false, 'models' => ['Picanto', 'Rio', 'Ceed', 'Sportage', 'Sorento', 'Stonic', 'Niro', 'EV6', 'EV9']],
            ['name' => 'Ford', 'popular' => false, 'models' => ['Fiesta', 'Focus', 'Mondeo', 'Kuga', 'Puma', 'Ranger', 'Mustang', 'Transit', 'Explorer']],
            ['name' => 'Renault', 'popular' => false, 'models' => ['Clio', 'Megane', 'Captur', 'Kadjar', 'Scenic', 'Talisman', 'Koleos', 'Zoe']],
            ['name' => 'Skoda', 'popular' => false, 'models' => ['Fabia', 'Octavia', 'Superb', 'Kamiq', 'Karoq', 'Kodiaq', 'Enyaq', 'Scala']],
            ['name' => 'Nissan', 'popular' => false, 'models' => ['Micra', 'Juke', 'Qashqai', 'X-Trail', 'Leaf', 'Ariya', 'Navara', 'GT-R']],
            ['name' => 'Honda', 'popular' => false, 'models' => ['Jazz', 'Civic', 'Accord', 'HR-V', 'CR-V', 'e:Ny1']],
            ['name' => 'Mazda', 'popular' => false, 'models' => ['2', '3', '6', 'CX-3', 'CX-30', 'CX-5', 'CX-60', 'MX-5']],
            ['name' => 'Volvo', 'popular' => false, 'models' => ['S60', 'S90', 'V60', 'V90', 'XC40', 'XC60', 'XC90', 'C40', 'EX30', 'EX90']],
            ['name' => 'Tesla', 'popular' => false, 'models' => ['Model 3', 'Model Y', 'Model S', 'Model X', 'Cybertruck']],
            ['name' => 'Citroen', 'popular' => false, 'models' => ['C3', 'C4', 'C5', 'C5 X', 'Berlingo', 'C3 Aircross', 'C5 Aircross']],
            ['name' => 'Dacia', 'popular' => false, 'models' => ['Sandero', 'Logan', 'Duster', 'Jogger', 'Spring']],
            ['name' => 'Seat', 'popular' => false, 'models' => ['Ibiza', 'Leon', 'Arona', 'Ateca', 'Tarraco', 'Cupra Formentor']],
            ['name' => 'Jeep', 'popular' => false, 'models' => ['Renegade', 'Compass', 'Cherokee', 'Grand Cherokee', 'Wrangler', 'Gladiator']],
            ['name' => 'Land Rover', 'popular' => false, 'models' => ['Defender', 'Discovery', 'Discovery Sport', 'Range Rover', 'Range Rover Sport', 'Range Rover Evoque', 'Range Rover Velar']],
            ['name' => 'Fiat', 'popular' => false, 'models' => ['500', 'Panda', 'Tipo', '500X', 'Doblo', 'Ducato']],
            ['name' => 'Mitsubishi', 'popular' => false, 'models' => ['Space Star', 'ASX', 'Eclipse Cross', 'Outlander', 'L200', 'Pajero']],
            ['name' => 'Suzuki', 'popular' => false, 'models' => ['Swift', 'Vitara', 'S-Cross', 'Jimny', 'Ignis']],
            ['name' => 'Lexus', 'popular' => false, 'models' => ['CT', 'IS', 'ES', 'GS', 'LS', 'UX', 'NX', 'RX', 'RZ']],
            ['name' => 'BYD', 'popular' => false, 'models' => ['Atto 3', 'Dolphin', 'Seal', 'Tang', 'Han']],
            ['name' => 'Dodge', 'popular' => false, 'models' => ['Challenger', 'Charger', 'Durango', 'Viper']],
        ];

        foreach ($brands as $index => $brandData) {
            $brand = VehicleBrand::query()->create([
                'name' => $brandData['name'],
                'slug' => Str::slug($brandData['name']),
                'is_popular' => $brandData['popular'] ?? false,
                'sort_order' => $index + 1,
            ]);

            if (isset($brandData['series'])) {
                foreach ($brandData['series'] as $seriesName => $models) {
                    $series = VehicleModel::query()->create([
                        'brand_id' => $brand->id,
                        'name' => $seriesName,
                        'slug' => Str::slug($seriesName),
                        'type' => 'series',
                        'sort_order' => 0,
                    ]);

                    foreach ($models as $mIndex => $modelName) {
                        VehicleModel::query()->create([
                            'brand_id' => $brand->id,
                            'parent_id' => $series->id,
                            'name' => $modelName,
                            'slug' => Str::slug($series->slug.'-'.$modelName),
                            'type' => 'model',
                            'sort_order' => $mIndex + 1,
                        ]);
                    }
                }
            } else {
                foreach ($brandData['models'] as $mIndex => $modelName) {
                    VehicleModel::query()->create([
                        'brand_id' => $brand->id,
                        'name' => $modelName,
                        'slug' => Str::slug($modelName),
                        'type' => 'model',
                        'sort_order' => $mIndex + 1,
                    ]);
                }
            }
        }
    }
}