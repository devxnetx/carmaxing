<?php

namespace Tests\Unit;

use App\Models\Listing;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use Tests\TestCase;

class ListingBreadcrumbTest extends TestCase
{
    public function test_breadcrumb_ad_name_includes_vehicle_name_and_ad_title(): void
    {
        $brand = new VehicleBrand(['name' => 'BMW']);
        $series = new VehicleModel(['name' => '5 Series']);
        $model = new VehicleModel(['name' => '530']);
        $model->setRelation('parent', $series);

        $listing = new Listing([
            'ad_name' => 'пълен лизинг',
            'car_variant' => 'xDrive',
        ]);
        $listing->setRelation('brand', $brand);
        $listing->setRelation('model', $model);

        $this->assertSame('BMW 5 Series 530 xDrive — пълен лизинг', $listing->breadcrumbAdName());
    }

    public function test_breadcrumb_ad_name_falls_back_to_vehicle_name_without_ad_title(): void
    {
        $brand = new VehicleBrand(['name' => 'Audi']);
        $model = new VehicleModel(['name' => 'A4']);

        $listing = new Listing;
        $listing->setRelation('brand', $brand);
        $listing->setRelation('model', $model);

        $this->assertSame('Audi A4', $listing->breadcrumbAdName());
    }
}