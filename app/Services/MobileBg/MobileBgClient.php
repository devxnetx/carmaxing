<?php

namespace App\Services\MobileBg;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class MobileBgClient
{
    private const USER_AGENT = 'CARMAXING-Importer/1.0 (+https://carmaxing.bg)';

    public function normalizeDealerUrl(string $url): string
    {
        $url = trim($url);

        if (! preg_match('~^https?://~i', $url)) {
            $url = 'https://'.$url;
        }

        $parts = parse_url($url);

        if (! $parts || empty($parts['host'])) {
            throw new InvalidArgumentException('Invalid Mobile.bg dealer URL.');
        }

        if (! str_ends_with(strtolower($parts['host']), '.mobile.bg')) {
            throw new InvalidArgumentException('URL must be a Mobile.bg dealer profile (e.g. https://ratola.mobile.bg/).');
        }

        $scheme = $parts['scheme'] ?? 'https';

        return $scheme.'://'.strtolower($parts['host']);
    }

    public function get(string $url): string
    {
        try {
            $response = Http::withHeaders(['User-Agent' => self::USER_AGENT])
                ->timeout(30)
                ->get($url);
        } catch (ConnectionException $e) {
            throw new InvalidArgumentException('Could not reach Mobile.bg: '.$e->getMessage(), previous: $e);
        }

        if (! $response->successful()) {
            throw new InvalidArgumentException('Mobile.bg returned HTTP '.$response->status().' for '.$url);
        }

        return $this->decodeBody($response->body());
    }

    /**
     * @param  array<string, string>  $fields
     */
    public function post(string $url, array $fields): string
    {
        try {
            $response = Http::asForm()
                ->withHeaders(['User-Agent' => self::USER_AGENT])
                ->timeout(30)
                ->post($url, $fields);
        } catch (ConnectionException $e) {
            throw new InvalidArgumentException('Could not reach Mobile.bg: '.$e->getMessage(), previous: $e);
        }

        if (! $response->successful()) {
            throw new InvalidArgumentException('Mobile.bg returned HTTP '.$response->status().' for '.$url);
        }

        return $this->decodeBody($response->body());
    }

    public function download(string $url): string
    {
        if (str_starts_with($url, '//')) {
            $url = 'https:'.$url;
        }

        try {
            $response = Http::withHeaders(['User-Agent' => self::USER_AGENT])
                ->timeout(60)
                ->get($url);
        } catch (ConnectionException $e) {
            throw new InvalidArgumentException('Could not download image: '.$e->getMessage(), previous: $e);
        }

        if (! $response->successful()) {
            throw new InvalidArgumentException('Image download failed with HTTP '.$response->status());
        }

        return $response->body();
    }

    private function decodeBody(string $body): string
    {
        if ($body === '') {
            return '';
        }

        $decoded = @iconv('WINDOWS-1251', 'UTF-8//IGNORE', $body);

        return $decoded !== false ? $decoded : $body;
    }
}