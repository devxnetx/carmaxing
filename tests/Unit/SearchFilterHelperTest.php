<?php

namespace Tests\Unit;

use App\Services\SearchFilterHelper;
use Illuminate\Http\Request;
use Tests\TestCase;

class SearchFilterHelperTest extends TestCase
{
    public function test_filters_from_request_excludes_ui_params(): void
    {
        $helper = app(SearchFilterHelper::class);

        $filters = $helper->filtersFromRequest(Request::create('/search', 'GET', [
            'brand_id' => 1,
            'page' => 2,
            'sort' => 'newest',
            'view' => 'grid',
            'scope' => 'imports',
        ]));

        $this->assertSame(['brand_id' => 1], $filters);
    }

    public function test_filters_hash_is_stable_for_equivalent_filters(): void
    {
        $helper = app(SearchFilterHelper::class);

        $hashA = $helper->filtersHash([
            'brand_id' => 1,
            'model_ids' => ['3', '2'],
        ]);

        $hashB = $helper->filtersHash([
            'model_ids' => [2, 3],
            'brand_id' => 1,
        ]);

        $this->assertSame($hashA, $hashB);
    }

    public function test_filters_from_request_normalizes_model_and_series_ids(): void
    {
        $helper = app(SearchFilterHelper::class);

        $filters = $helper->filtersFromRequest(Request::create('/search', 'GET', [
            'brand_id' => '5',
            'model_ids' => ['12', '12', '0'],
            'series_ids' => ['3'],
        ]));

        $this->assertSame([
            'brand_id' => 5,
            'model_ids' => [12],
            'series_ids' => [3],
        ], $filters);
    }

    public function test_should_open_extended_search_when_no_criteria_selected(): void
    {
        $helper = app(SearchFilterHelper::class);

        $this->assertTrue($helper->shouldOpenExtendedSearch(Request::create('/search', 'GET')));
        $this->assertTrue($helper->shouldOpenExtendedSearch(Request::create('/search', 'GET', [
            'view' => 'grid',
            'sort' => 'newest',
            'page' => 2,
        ])));
        $this->assertFalse($helper->shouldOpenExtendedSearch(Request::create('/search', 'GET', [
            'brand_id' => 1,
        ])));
        $this->assertTrue($helper->shouldOpenExtendedSearch(Request::create('/search', 'GET', [
            'brand_id' => 1,
            'fuel_type' => ['diesel'],
        ])));
    }

    public function test_search_url_from_filters_includes_array_parameters(): void
    {
        $helper = app(SearchFilterHelper::class);

        $url = $helper->searchUrlFromFilters([
            'brand_id' => 1,
            'model_ids' => [3, 5],
            'series_ids' => [2],
        ]);

        $this->assertStringContainsString('brand_id=1', $url);
        $this->assertStringContainsString('model_ids', $url);
        $this->assertStringContainsString('series_ids', $url);

        parse_str(parse_url($url, PHP_URL_QUERY), $query);

        $this->assertSame('1', (string) ($query['brand_id'] ?? ''));
        $this->assertSame(['3', '5'], array_values((array) ($query['model_ids'] ?? [])));
        $this->assertSame(['2'], array_values((array) ($query['series_ids'] ?? [])));
    }
}