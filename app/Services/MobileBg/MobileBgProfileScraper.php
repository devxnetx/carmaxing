<?php

namespace App\Services\MobileBg;

use App\Support\HtmlToPlainText;

class MobileBgProfileScraper
{
    public function __construct(
        private readonly MobileBgClient $client,
    ) {}

    public function scrape(string $dealerUrl): MobileBgProfileData
    {
        $sourceUrl = $this->client->normalizeDealerUrl($dealerUrl);
        $homeHtml = $this->client->get($sourceUrl.'/');
        $aboutHtml = $this->client->get($sourceUrl.'/about');
        $contactsHtml = $this->client->get($sourceUrl.'/contacts');

        $name = $this->parseName($homeHtml, $aboutHtml);
        $description = $this->parseDescription($aboutHtml);
        $phones = $this->parsePhones($contactsHtml);
        $phone = $this->preferMobilePhone($phones);
        $location = $this->parseLocation($contactsHtml);

        return new MobileBgProfileData(
            sourceUrl: $sourceUrl,
            name: $name,
            description: $description,
            phone: $phone,
            address: $location['address'],
            city: $location['city'],
            regionName: $location['region'],
            website: $this->parseWebsite($aboutHtml, $description, $sourceUrl),
            logoUrl: $this->parseAssetUrl($homeHtml, 'houseslogos'),
            coverUrl: $this->parseAssetUrl($homeHtml, 'housespicts'),
        );
    }

    private function parseName(string $homeHtml, string $aboutHtml): string
    {
        if (preg_match('/<h1>\s*За нас\s*-\s*([^<]+)\s*<\/h1>/u', $aboutHtml, $match)) {
            return trim(html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }

        if (preg_match('/<title>[^-]*-\s*Автокъща\s+([^,]+),/u', $homeHtml, $match)) {
            return trim(html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }

        if (preg_match('/<h1>\s*([^<]+)\s*<\/h1>/u', $homeHtml, $match)) {
            return trim(html_entity_decode(strip_tags($match[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }

        return 'Dealer';
    }

    private function parseDescription(string $aboutHtml): ?string
    {
        if (! preg_match('/<div class="about">.*?<div class="text">\s*(.*?)\s*<\/div>/su', $aboutHtml, $match)) {
            return null;
        }

        return HtmlToPlainText::convert($match[1]);
    }

    /** @return list<string> */
    private function parsePhones(string $contactsHtml): array
    {
        $phones = [];

        if (preg_match('/<div class="phone">\s*([^<]+)(.*?)<\/div>/su', $contactsHtml, $match)) {
            $phones[] = trim($match[1]);

            if (preg_match_all('/<span>\s*([^<]+)\s*<\/span>/u', $match[2], $spans)) {
                foreach ($spans[1] as $span) {
                    $phones[] = trim($span);
                }
            }
        }

        if ($phones === [] && preg_match_all('/\b0[89]\d{8}\b/u', $contactsHtml, $matches)) {
            $phones = $matches[0];
        }

        return array_values(array_unique(array_filter($phones)));
    }

    /** @param  list<string>  $phones */
    private function preferMobilePhone(array $phones): ?string
    {
        foreach ($phones as $phone) {
            $digits = preg_replace('/\D+/', '', $phone) ?? '';

            if (preg_match('/^0[89]\d{8}$/', $digits)) {
                return $digits;
            }
        }

        return isset($phones[0]) ? (preg_replace('/\D+/', '', $phones[0]) ?: null) : null;
    }

    /**
     * @return array{address: ?string, city: ?string, region: ?string}
     */
    private function parseLocation(string $contactsHtml): array
    {
        $address = null;
        $city = null;
        $region = null;

        if (preg_match('/Местоположение:\s*<strong>([^<]+)<\/strong>/u', $contactsHtml, $match)) {
            $address = trim(html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $address = trim(preg_replace('/\s+GPS\s+[\d.,-]+$/u', '', $address) ?? $address);
        }

        if (preg_match('/Регион:\s*<strong>([^<]+)<\/strong>/u', $contactsHtml, $match)) {
            $regionText = trim(html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));

            if (preg_match('/^гр\.\s*([^,]+)(?:,\s*(.+))?$/u', $regionText, $parts)) {
                $city = trim($parts[1]);
                $district = trim($parts[2] ?? '');
                if ($district !== '') {
                    $address = $address ?: $district;
                }
                $region = 'София-град';
            } elseif (preg_match('/^обл\.\s*(.+)$/u', $regionText, $parts)) {
                $region = trim($parts[1]);
            }
        }

        return [
            'address' => $address,
            'city' => $city,
            'region' => $region,
        ];
    }

    private function parseWebsite(?string $aboutHtml, ?string $description, string $sourceUrl): ?string
    {
        $candidates = [];

        if ($description && preg_match_all('#\b((?:https?://)?(?:www\.)?[a-z0-9][a-z0-9.-]+\.[a-z]{2,})\b#iu', $description, $matches)) {
            $candidates = array_merge($candidates, $matches[1]);
        }

        if ($aboutHtml && preg_match_all('#\bhref="(https?://[^"]+)"#iu', $aboutHtml, $matches)) {
            foreach ($matches[1] as $url) {
                if (! str_contains($url, 'mobile.bg')) {
                    $candidates[] = $url;
                }
            }
        }

        foreach ($candidates as $candidate) {
            $candidate = trim($candidate);

            if ($candidate === '' || str_contains($candidate, 'mobile.bg')) {
                continue;
            }

            if (! preg_match('~^https?://~i', $candidate)) {
                $candidate = 'https://'.$candidate;
            }

            return $candidate;
        }

        $host = parse_url($sourceUrl, PHP_URL_HOST);
        $slug = $host ? explode('.', $host)[0] : null;

        return $slug ? 'https://www.'.$slug.'.bg' : null;
    }

    private function parseAssetUrl(string $html, string $folder): ?string
    {
        if (! preg_match('#//cdn2\.focus\.bg/mobile/images/'.$folder.'/([^"\']+)#i', $html, $match)) {
            return null;
        }

        return 'https://cdn2.focus.bg/mobile/images/'.$folder.'/'.$match[1];
    }
}