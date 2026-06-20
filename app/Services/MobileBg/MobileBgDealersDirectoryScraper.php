<?php

namespace App\Services\MobileBg;

use InvalidArgumentException;

class MobileBgDealersDirectoryScraper
{
    public function __construct(
        private readonly MobileBgClient $client,
    ) {}

    public function normalizeDirectoryUrl(string $url): string
    {
        $url = trim($url);

        if (! preg_match('~^https?://~i', $url)) {
            $url = 'https://'.$url;
        }

        $parts = parse_url($url);

        if (! $parts || empty($parts['host']) || strtolower($parts['host']) !== 'www.mobile.bg') {
            throw new InvalidArgumentException('URL must be a Mobile.bg dealers directory page.');
        }

        $path = $parts['path'] ?? '/dealers';

        if (! str_starts_with($path, '/dealers')) {
            throw new InvalidArgumentException('URL must point to a Mobile.bg dealers listing.');
        }

        $path = preg_replace('#/p-\d+/?$#', '', $path) ?? $path;
        $path = rtrim($path, '/') ?: '/dealers';

        return 'https://www.mobile.bg'.$path;
    }

    /**
     * @return array{slug: ?string, label: ?string}
     */
    public function parseCityFromUrl(string $url): array
    {
        $normalized = $this->normalizeDirectoryUrl($url);

        if (! preg_match('#/dealers/location-([^/]+)$#', $normalized, $match)) {
            return ['slug' => null, 'label' => null];
        }

        $slug = $match[1];
        $label = preg_replace('/^(grad|selo)-/', '', $slug) ?? $slug;
        $label = str_replace('-', ' ', $label);

        return [
            'slug' => $slug,
            'label' => mb_convert_case($label, MB_CASE_TITLE, 'UTF-8'),
        ];
    }

    public function pageUrl(string $baseUrl, int $page): string
    {
        $baseUrl = $this->normalizeDirectoryUrl($baseUrl);

        if ($page <= 1) {
            return $baseUrl;
        }

        return $baseUrl.'/p-'.$page;
    }

    public function lastPageNumber(string $html): int
    {
        $pages = [];

        if (preg_match_all('#/dealers(?:/[^"\'\s]+)?/p-(\d+)#', $html, $matches)) {
            $pages = array_map('intval', $matches[1]);
        }

        if (preg_match_all('#dealers/p-(\d+)#', $html, $matches)) {
            $pages = array_merge($pages, array_map('intval', $matches[1]));
        }

        if (preg_match_all('#>(\d{1,4})</a>#', $html, $matches)) {
            foreach ($matches[1] as $page) {
                $value = (int) $page;
                if ($value > 1) {
                    $pages[] = $value;
                }
            }
        }

        return $pages === [] ? 1 : max($pages);
    }

    /**
     * @return list<string>
     */
    public function collectDealerUrls(string $directoryUrl): array
    {
        $baseUrl = $this->normalizeDirectoryUrl($directoryUrl);
        $firstHtml = $this->client->get($baseUrl);
        $lastPage = $this->lastPageNumber($firstHtml);
        $urls = $this->parseDealerUrls($firstHtml);

        for ($page = 2; $page <= $lastPage; $page++) {
            usleep(500_000);
            $html = $this->client->get($this->pageUrl($baseUrl, $page));
            $urls = array_merge($urls, $this->parseDealerUrls($html));
        }

        return array_values(array_unique($urls));
    }

    /**
     * @return list<string>
     */
    public function parseDealerUrls(string $html): array
    {
        if (! preg_match_all('#href="(https://[a-z0-9][a-z0-9-]*\.mobile\.bg)"#i', $html, $matches)) {
            return [];
        }

        $urls = [];

        foreach ($matches[1] as $url) {
            $host = parse_url($url, PHP_URL_HOST);

            if (! $host || strtolower($host) === 'www.mobile.bg') {
                continue;
            }

            try {
                $urls[] = $this->client->normalizeDealerUrl($url);
            } catch (InvalidArgumentException) {
                continue;
            }
        }

        return array_values(array_unique($urls));
    }
}