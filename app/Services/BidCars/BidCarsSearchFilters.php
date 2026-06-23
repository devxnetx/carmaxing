<?php

namespace App\Services\BidCars;

use App\Support\BidCarsImportConfig;

class BidCarsSearchFilters
{
    public function __construct(
        public string $make = 'All',
        public int $page = 1,
        public ?int $perPage = null,
    ) {}

    /**
     * @return array<string, string|int>
     */
    public static function baseParams(): array
    {
        return BidCarsImportConfig::filters();
    }

    /**
     * @return array<string, string|int>
     */
    public function toQueryParams(): array
    {
        $params = self::baseParams();
        $params['make'] = $this->make;
        $params['page'] = $this->page;

        if ($this->perPage !== null) {
            $params['per_page'] = $this->perPage;
        }

        return $params;
    }

    public function resultsRefererUrl(): string
    {
        return 'https://bid.cars/en/search/results?'.$this->toQueryString(
            array_diff_key($this->toQueryParams(), ['per_page' => true]),
        );
    }

    public function requestUrl(): string
    {
        return 'https://bid.cars/app/search/request?'.$this->toQueryString($this->toQueryParams());
    }

    /**
     * @param  array<string, string|int>  $params
     */
    private function toQueryString(array $params): string
    {
        return http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }
}