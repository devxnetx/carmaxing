<?php

namespace App\Services\MobileBg;

use App\Support\HtmlToPlainText;

class MobileBgScraper
{
    public function __construct(
        private readonly MobileBgClient $client,
    ) {}

    /**
     * @return list<array{external_id: string, url: string}>
     */
    public function countListings(string $dealerUrl): int
    {
        return count($this->collectListingRefs($dealerUrl));
    }

    public function collectListingRefs(string $dealerUrl): array
    {
        $dealerUrl = $this->client->normalizeDealerUrl($dealerUrl);
        $firstPageHtml = $this->client->get($dealerUrl.'/');
        $formFields = $this->extractSearchFormFields($firstPageHtml);
        $totalPages = $this->extractTotalPages($firstPageHtml);

        $refs = $this->parseListingRefs($firstPageHtml, $dealerUrl);

        for ($page = 2; $page <= $totalPages; $page++) {
            usleep(300_000);
            $formFields['page'] = (string) $page;
            $html = $this->client->post($dealerUrl.'/', $formFields);
            $refs = array_merge($refs, $this->parseListingRefs($html, $dealerUrl));
        }

        return $this->uniqueRefs($refs);
    }

    public function scrapeAd(string $url): MobileBgAdData
    {
        $html = $this->client->get($url);

        return $this->parseDetailPage($html, $url);
    }

    /**
     * @return array<string, string>
     */
    private function extractSearchFormFields(string $html): array
    {
        $hf = $this->matchOne($html, '/name="hf"\s+value="([^"]+)"/');

        return [
            'extended' => '0',
            'hf' => $hf ?? '',
            'topmenu' => '1',
            'rub_pub_save' => '1',
            'abonament_flag' => '1',
            'page' => '1',
            'pubtype' => '1',
            'sort' => '2',
            'nup' => '01234',
        ];
    }

    private function extractTotalPages(string $html): int
    {
        if (preg_match('/Страница\s+\d+\s+от\s+(\d+)/u', $html, $matches)) {
            return max(1, (int) $matches[1]);
        }

        if (preg_match('/от\s+(\d+)\s*<\/div>/u', $html, $matches)) {
            return max(1, (int) $matches[1]);
        }

        return 1;
    }

    /**
     * @return list<array{external_id: string, url: string}>
     */
    private function parseListingRefs(string $html, string $dealerUrl): array
    {
        $refs = [];

        if (preg_match_all('/id="ida(\d{10,})"/', $html, $idMatches)) {
            foreach ($idMatches[1] as $externalId) {
                if (preg_match('/href="(https?:\/\/[^"]*\/obiava-'.preg_quote($externalId, '/').'[^"]*)"/', $html, $urlMatch)) {
                    $refs[] = [
                        'external_id' => $externalId,
                        'url' => html_entity_decode($urlMatch[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                    ];
                }
            }
        }

        if ($refs !== []) {
            return $refs;
        }

        if (preg_match_all('/href="(https?:\/\/[^"]*\/obiava-(\d{10,})[^"]*)"/', $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $refs[] = [
                    'external_id' => $match[2],
                    'url' => html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                ];
            }
        }

        return $refs;
    }

    /**
     * @param  list<array{external_id: string, url: string}>  $refs
     * @return list<array{external_id: string, url: string}>
     */
    private function uniqueRefs(array $refs): array
    {
        $seen = [];
        $unique = [];

        foreach ($refs as $ref) {
            if (isset($seen[$ref['external_id']])) {
                continue;
            }

            $seen[$ref['external_id']] = true;
            $unique[] = $ref;
        }

        return $unique;
    }

    private function parseDetailPage(string $html, string $url): MobileBgAdData
    {
        $externalId = $this->matchOne($html, '/Обява:\s*(\d{10,})/u')
            ?? $this->matchOne($url, '/obiava-(\d{10,})/')
            ?? throw new \RuntimeException('Could not determine Mobile.bg ad ID.');

        [$brandName, $modelName, $variant] = $this->parseTitle($html);
        $specs = $this->parseSpecs($html);
        $price = $this->parsePrice($html);
        $location = $this->parseLocation($html);

        return new MobileBgAdData(
            externalId: $externalId,
            url: $url,
            brandName: $brandName,
            modelName: $modelName,
            variant: $variant,
            title: trim(implode(' ', array_filter([$brandName, $modelName, $variant]))),
            price: $price['amount'],
            currency: $price['currency'],
            priceOnRequest: $price['on_request'],
            year: $specs['year'] ?? 0,
            month: $specs['month'] ?? null,
            mileage: $specs['mileage'] ?? null,
            fuelType: $specs['fuel_type'] ?? null,
            enginePowerHp: $specs['engine_power_hp'] ?? null,
            engineDisplacementCc: $specs['engine_displacement_cc'] ?? null,
            transmission: $specs['transmission'] ?? null,
            bodyType: $specs['body_type'] ?? null,
            colorExterior: $specs['color_exterior'] ?? null,
            euroStandard: $specs['euro_standard'] ?? null,
            city: $location['city'],
            regionName: $location['region'],
            description: $this->parseDescription($html),
            imageUrls: $this->parseImages($html, $externalId),
        );
    }

    /**
     * @return array{0: string, 1: string, 2: ?string}
     */
    private function parseTitle(string $html): array
    {
        if (preg_match('/<h1[^>]*>(.*?)<\/h1>/su', $html, $match)) {
            $heading = $match[1];
            $brandModel = trim(strip_tags(preg_replace('/<div[^>]*class="obiava"[^>]*>.*?<\/div>/su', '', $heading)));
            $variant = null;

            if (preg_match('/<span[^>]*>(.*?)<\/span>/su', $heading, $variantMatch)) {
                $variant = trim(strip_tags($variantMatch[1]));
            }

            $parts = preg_split('/\s+/', $brandModel, 3);
            $brand = $parts[0] ?? 'Unknown';
            $model = $parts[1] ?? ($parts[0] ?? 'Unknown');

            if (isset($parts[2]) && $variant === null) {
                $variant = $parts[2];
            }

            return [$brand, $model, $variant ?: null];
        }

        if (preg_match('/class="title">([^<]+)</', $html, $match)) {
            $parts = preg_split('/\s+/', trim($match[1]), 3);

            return [
                $parts[0] ?? 'Unknown',
                $parts[1] ?? 'Unknown',
                $parts[2] ?? null,
            ];
        }

        return ['Unknown', 'Unknown', null];
    }

    /**
     * @return array<string, mixed>
     */
    private function parseSpecs(string $html): array
    {
        $specs = [];

        if (preg_match_all('/<div class="item">\s*<div>([^<]+)<\/div>\s*<div>([^<]+)<\/div>/su', $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $label = trim($match[1]);
                $value = trim(html_entity_decode($match[2], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                $this->applySpecLabel($specs, $label, $value);
            }
        }

        if (preg_match_all('/<div class="mpLabel">([^<]*)<\/div>\s*<div class="mpInfo">([^<]*)<\/div>/su', $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $this->applySpecLabel($specs, trim($match[1]), trim($match[2]));
            }
        }

        return $specs;
    }

    /**
     * @param  array<string, mixed>  $specs
     */
    private function applySpecLabel(array &$specs, string $label, string $value): void
    {
        $label = mb_strtolower($label);

        if (str_contains($label, 'дата на производство') || str_contains($label, 'година')) {
            [$year, $month] = $this->parseProductionDate($value);
            $specs['year'] = $year;
            $specs['month'] = $month;

            return;
        }

        if (str_contains($label, 'двигател') || str_contains($label, 'гориво')) {
            $specs['fuel_type'] = $this->mapFuelType($value);

            return;
        }

        if (str_contains($label, 'мощност')) {
            if (preg_match('/(\d+)/', $value, $m)) {
                $specs['engine_power_hp'] = (int) $m[1];
            }

            return;
        }

        if (str_contains($label, 'куб')) {
            if (preg_match('/(\d+)/', $value, $m)) {
                $specs['engine_displacement_cc'] = (int) $m[1];
            }

            return;
        }

        if (str_contains($label, 'скорост')) {
            $specs['transmission'] = $this->mapTransmission($value);

            return;
        }

        if (str_contains($label, 'пробег')) {
            if (preg_match('/(\d+)/', str_replace(' ', '', $value), $m)) {
                $specs['mileage'] = (int) $m[1];
            }

            return;
        }

        if (str_contains($label, 'цвят')) {
            $specs['color_exterior'] = $value;

            return;
        }

        if (str_contains($label, 'категория') || str_contains($label, 'тип')) {
            $specs['body_type'] = $this->mapBodyType($value);

            return;
        }

        if (str_contains($label, 'евростандарт') || str_contains($label, 'евро')) {
            $specs['euro_standard'] = $value;
        }
    }

    /**
     * @return array{0: int, 1: ?int}
     */
    private function parseProductionDate(string $value): array
    {
        $months = [
            'януари' => 1, 'февруари' => 2, 'март' => 3, 'април' => 4,
            'май' => 5, 'юни' => 6, 'юли' => 7, 'август' => 8,
            'септември' => 9, 'октомври' => 10, 'ноември' => 11, 'декември' => 12,
        ];

        $value = mb_strtolower(trim($value));
        $month = null;
        $year = (int) date('Y');

        foreach ($months as $name => $number) {
            if (str_contains($value, $name)) {
                $month = $number;
                break;
            }
        }

        if (preg_match('/(19|20)\d{2}/', $value, $match)) {
            $year = (int) $match[0];
        }

        return [$year, $month];
    }

    private function mapFuelType(string $value): ?string
    {
        $value = mb_strtolower($value);

        return match (true) {
            str_contains($value, 'дизел') => 'diesel',
            str_contains($value, 'бензин') => 'petrol',
            str_contains($value, 'електр') => 'electric',
            str_contains($value, 'plug') || str_contains($value, 'plug-in') => 'plug-in-hybrid',
            str_contains($value, 'хибрид') => 'hybrid',
            str_contains($value, 'lpg') || str_contains($value, 'газ') => 'lpg',
            str_contains($value, 'cng') || str_contains($value, 'метан') => 'cng',
            default => null,
        };
    }

    private function mapTransmission(string $value): ?string
    {
        $value = mb_strtolower($value);

        return match (true) {
            str_contains($value, 'автомат') => 'automatic',
            str_contains($value, 'ръчн') => 'manual',
            str_contains($value, 'полуавтомат') => 'semi-automatic',
            default => null,
        };
    }

    private function mapBodyType(string $value): ?string
    {
        $value = mb_strtolower($value);

        return match (true) {
            str_contains($value, 'джип') || str_contains($value, 'suv') => 'suv',
            str_contains($value, 'седан') => 'sedan',
            str_contains($value, 'хечбек') || str_contains($value, 'хечб') => 'hatchback',
            str_contains($value, 'комби') || str_contains($value, 'ван') || str_contains($value, 'естейт') => 'wagon',
            str_contains($value, 'купе') || str_contains($value, 'кабрио') => 'coupe',
            str_contains($value, 'кабриолет') || str_contains($value, 'кабрио') => 'cabrio',
            str_contains($value, 'пикап') => 'pickup',
            str_contains($value, 'миниван') => 'van',
            default => null,
        };
    }

    /**
     * @return array{amount: ?int, currency: string, on_request: bool}
     */
    private function parsePrice(string $html): array
    {
        if (preg_match('/при\s+запитване/ui', $html)) {
            return ['amount' => null, 'currency' => 'EUR', 'on_request' => true];
        }

        if (preg_match("/showpricechange\('(\d+)','(\d+)','([A-Z]+)'/", $html, $match)) {
            return [
                'amount' => (int) $match[2],
                'currency' => $match[3],
                'on_request' => false,
            ];
        }

        if (preg_match('/class="price[^"]*">\s*<div>([\d\s]+)\s*€/u', $html, $match)) {
            $amount = (int) str_replace(' ', '', $match[1]);

            return ['amount' => $amount, 'currency' => 'EUR', 'on_request' => false];
        }

        return ['amount' => null, 'currency' => 'EUR', 'on_request' => true];
    }

    /**
     * @return array{city: ?string, region: ?string}
     */
    private function parseLocation(string $html): array
    {
        if (preg_match('/<em>обл\.\s*([^,]+),\s*гр\.\s*([^<]+)<\/em>/u', $html, $match)) {
            return [
                'region' => trim($match[1]),
                'city' => trim($match[2]),
            ];
        }

        if (preg_match('/обл\.\s*([^,]+),\s*гр\.\s*([^\s<]+)/u', $html, $match)) {
            return [
                'region' => trim($match[1]),
                'city' => trim($match[2]),
            ];
        }

        return ['city' => null, 'region' => null];
    }

    private function parseDescription(string $html): ?string
    {
        if (preg_match('/<div class="moreInfo"[^>]*>.*?<div class="text">\s*(.*?)\s*<\/div>/su', $html, $match)) {
            return HtmlToPlainText::convert($match[1]);
        }

        if (preg_match('/<div class="moreInfo"[^>]*>(.*?)<\/div>/su', $html, $match)) {
            return HtmlToPlainText::convert($match[1]);
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function parseImages(string $html, string $externalId): array
    {
        $urls = [];

        if (preg_match_all('/data-src="(https?:\/\/[^"]+)"/', $html, $matches)) {
            foreach ($matches[1] as $url) {
                if (str_contains($url, $externalId)) {
                    $urls[] = $url;
                }
            }
        }

        if ($urls === [] && preg_match_all('/src="(\/\/mobistatic[^"]+\.(?:webp|jpg|jpeg|png)[^"]*)"/', $html, $matches)) {
            foreach ($matches[1] as $url) {
                if (str_contains($url, $externalId)) {
                    $urls[] = 'https:'.html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                }
            }
        }

        return array_values(array_unique($urls));
    }

    private function matchOne(string $html, string $pattern): ?string
    {
        return preg_match($pattern, $html, $matches) ? $matches[1] : null;
    }
}