<?php

namespace Tests\Unit;

use App\Models\VehicleBrand;
use App\Support\CatalogCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CatalogCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_brands_returns_hydrated_models_from_database_cache(): void
    {
        $this->seed(\Database\Seeders\CatalogSeeder::class);
        Cache::flush();

        $first = CatalogCache::brands();
        $second = CatalogCache::brands();

        $this->assertInstanceOf(Collection::class, $first);
        $this->assertGreaterThan(0, $first->count());
        $this->assertInstanceOf(VehicleBrand::class, $first->first());
        $this->assertSame($first->first()->id, $second->first()->id);
    }
}