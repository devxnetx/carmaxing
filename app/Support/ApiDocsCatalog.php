<?php

namespace App\Support;

class ApiDocsCatalog
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function endpoints(string $baseUrl, int $maxPerPage, ?string $sampleListingId = '1001'): array
    {
        $listingPath = '/listings/'.$sampleListingId;

        return [
            [
                'id' => 'catalog',
                'method' => 'GET',
                'path' => '/catalog',
                'api_path' => '/catalog',
                'desc' => 'Brands, models (hierarchical), fuel types, body types.',
                'runnable' => true,
                'request' => null,
                'try_query' => null,
                'try_body' => null,
                'response' => self::catalogResponse(),
                'php' => ApiDocsPhpExample::build('GET', $baseUrl.'/catalog'),
            ],
            [
                'id' => 'listings',
                'method' => 'GET',
                'path' => '/listings',
                'api_path' => '/listings',
                'desc' => 'List your company listings. Query: status, ad_number, external_id, per_page (max '.$maxPerPage.').',
                'runnable' => true,
                'request' => null,
                'try_query' => ['per_page' => 5],
                'try_body' => null,
                'response' => self::listingsResponse(),
                'php' => ApiDocsPhpExample::build('GET', $baseUrl.'/listings', null, ['per_page' => 5]),
            ],
            [
                'id' => 'listings_show',
                'method' => 'GET',
                'path' => '/listings/{id}',
                'api_path' => '/listings/{id}',
                'desc' => 'Get one listing by id, ad_number, or external_id.',
                'runnable' => true,
                'needs_listing' => true,
                'request' => null,
                'try_query' => null,
                'try_body' => null,
                'response' => self::listingShowResponse(),
                'php' => ApiDocsPhpExample::build('GET', $baseUrl.$listingPath),
            ],
            [
                'id' => 'listings_create',
                'method' => 'POST',
                'path' => '/listings',
                'api_path' => '/listings',
                'desc' => 'Create and publish a listing. ad_number is assigned automatically.',
                'runnable' => true,
                'request' => self::createRequestBody(),
                'try_body' => [
                    'external_id' => 'docs-playground',
                    'brand_id' => 29,
                    'model_id' => 142,
                    'car_variant' => '6.4 Shaker',
                    'ad_name' => 'пълен лизинг, даунпайп',
                    'description' => 'Тестова обява от API документацията.',
                    'price' => 52900,
                    'price_on_request' => false,
                    'currency' => 'EUR',
                    'year' => 2016,
                    'mileage' => 68420,
                    'fuel_type' => 'petrol',
                    'city' => 'София',
                ],
                'try_query' => null,
                'response' => self::createResponse(),
                'php' => ApiDocsPhpExample::build('POST', $baseUrl.'/listings', [
                    'external_id' => 'docs-playground',
                    'brand_id' => 29,
                    'model_id' => 142,
                    'car_variant' => '6.4 Shaker',
                    'ad_name' => 'пълен лизинг, даунпайп',
                    'description' => 'Тестова обява от API документацията.',
                    'price' => 52900,
                    'price_on_request' => false,
                    'currency' => 'EUR',
                    'year' => 2016,
                    'mileage' => 68420,
                    'fuel_type' => 'petrol',
                    'city' => 'София',
                ]),
            ],
            [
                'id' => 'listings_update',
                'method' => 'PUT',
                'path' => '/listings/{id}',
                'api_path' => '/listings/{id}',
                'desc' => 'Partial update. Same fields as POST.',
                'runnable' => true,
                'needs_listing' => true,
                'request' => '{ "price": 49900, "ad_name": "намалена цена, лизинг" }',
                'try_body' => [
                    'price' => 49900,
                    'ad_name' => 'намалена цена, лизинг',
                ],
                'try_query' => null,
                'response' => '{ "data": { "id": 1, "ad_number": 1001, "price": 49900, "title": "..." } }',
                'php' => ApiDocsPhpExample::build('PUT', $baseUrl.$listingPath, [
                    'price' => 49900,
                    'ad_name' => 'намалена цена, лизинг',
                ]),
            ],
            [
                'id' => 'listings_delete',
                'method' => 'DELETE',
                'path' => '/listings/{id}',
                'api_path' => null,
                'desc' => 'Archive listing (soft delete).',
                'runnable' => false,
                'playground_disabled' => true,
                'request' => null,
                'response' => '{ "message": "Listing archived.", "data": { "id": 1, "status": "archived" } }',
                'php' => null,
            ],
        ];
    }

    private static function catalogResponse(): string
    {
        return <<<'JSON'
{
  "brands": [
    {
      "id": 29,
      "name": "Dodge",
      "slug": "dodge",
      "models": [
        { "id": 142, "name": "Challenger", "slug": "challenger", "type": "model", "children": [] }
      ]
    }
  ],
  "fuel_types": ["petrol", "diesel", "electric", "hybrid", "plug-in-hybrid"],
  "transmissions": ["manual", "automatic", "semi-automatic"],
  "drivetrains": ["fwd", "rwd", "awd", "4x4"],
  "body_types": ["sedan", "suv", "coupe"]
}
JSON;
    }

    private static function listingsResponse(): string
    {
        return <<<'JSON'
{
  "current_page": 1,
  "data": [
    {
      "id": 1,
      "ad_number": 1001,
      "external_id": "ERP-4521",
      "brand_id": 29,
      "model_id": 142,
      "car_variant": "6.4 Shaker",
      "ad_name": "пълен лизинг, даунпайп",
      "title": "Dodge Challenger 6.4 Shaker пълен лизинг, даунпайп",
      "price": 52900,
      "price_on_request": false,
      "currency": "EUR",
      "year": 2016,
      "status": "published"
    }
  ],
  "per_page": 25,
  "total": 1
}
JSON;
    }

    private static function listingShowResponse(): string
    {
        return <<<'JSON'
{
  "data": {
    "id": 1,
    "ad_number": 1001,
    "car_variant": "6.4 Shaker",
    "ad_name": "пълен лизинг, даунпайп",
    "brand": { "id": 29, "name": "Dodge" },
    "model": { "id": 142, "name": "Challenger" },
    "features": [{ "id": 12, "name_bg": "4x4" }],
    "images": []
  }
}
JSON;
    }

    private static function createRequestBody(): string
    {
        return <<<'JSON'
{
  "external_id": "ERP-4521",
  "brand_id": 29,
  "model_id": 142,
  "car_variant": "6.4 Shaker",
  "ad_name": "пълен лизинг, даунпайп",
  "description": "Отлично състояние...",
  "price": 52900,
  "price_on_request": false,
  "currency": "EUR",
  "year": 2016,
  "mileage": 68420,
  "fuel_type": "petrol",
  "engine_power_hp": 485,
  "transmission": "automatic",
  "drivetrain": "rwd",
  "body_type": "coupe",
  "region_id": 1,
  "city": "София",
  "feature_ids": [1, 5, 12, 28]
}
JSON;
    }

    private static function createResponse(): string
    {
        return <<<'JSON'
{
  "data": {
    "id": 1,
    "ad_number": 1001,
    "slug": "dodge-challenger-6-4-shaker-abc123",
    "title": "Dodge Challenger 6.4 Shaker пълен лизинг, даунпайп",
    "status": "published",
    "published_at": "2026-06-20T12:00:00.000000Z"
  }
}
JSON;
    }
}