<?php

namespace App\Services\BidCars;

use App\Support\BidCarsImportConfig;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use InvalidArgumentException;
use RuntimeException;

class BidCarsClient
{
    private const USER_AGENT = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36';

    /**
     * @param  list<string>  $brands
     * @return list<array{brand: string, current_page: int, payload: array<string, mixed>}>
     */
    public function fetchSessionPages(array $brands, int|string $pagesPerBrand = 3): array
    {
        $script = base_path('scripts/bid-cars-session.mjs');

        if (! is_file($script)) {
            throw new RuntimeException('Bid.cars session script is missing at '.$script);
        }

        $env = [
            'BID_CARS_HEADLESS' => BidCarsImportConfig::headless() ? '1' : '0',
        ];

        $timeoutSeconds = BidCarsImportConfig::isFullPages($pagesPerBrand) ? 7_200 : 900;

        $result = Process::timeout($timeoutSeconds)->env($env)->run([
            'node',
            $script,
            '--brands='.implode(',', $brands),
            '--pages='.$pagesPerBrand,
        ]);

        if (! $result->successful()) {
            $error = trim($result->errorOutput()) ?: trim($result->output());

            throw new RuntimeException('Bid.cars browser session failed: '.$error);
        }

        $envelope = json_decode(trim($result->output()), true);

        if (! is_array($envelope) || ($envelope['ok'] ?? false) !== true) {
            throw new RuntimeException('Bid.cars browser session failed: '.($envelope['error'] ?? 'unknown error'));
        }

        $pages = $envelope['pages'] ?? [];

        if (! is_array($pages) || $pages === []) {
            throw new RuntimeException('Bid.cars browser session returned no pages.');
        }

        return $pages;
    }

    /**
     * @return array<string, mixed>
     */
    public function fetchSearchPayload(BidCarsSearchFilters $filters): array
    {
        $url = $filters->requestUrl();
        $referer = $filters->resultsRefererUrl();

        $body = $this->fetchWithBrowserScript($referer)
            ?? $this->fetchWithHttp($url, $referer);

        $payload = json_decode($body, true);

        if (! is_array($payload)) {
            throw new RuntimeException('Bid.cars returned invalid JSON for '.$url);
        }

        return $payload;
    }

    private function fetchWithBrowserScript(string $referer): ?string
    {
        $script = base_path('scripts/bid-cars-fetch.mjs');

        if (! is_file($script)) {
            return null;
        }

        $env = [
            'BID_CARS_HEADLESS' => BidCarsImportConfig::headless() ? '1' : '0',
        ];

        $result = Process::timeout(180)->env($env)->run([
            'node',
            $script,
            '--referer='.$referer,
        ]);

        if (! $result->successful()) {
            $error = trim($result->errorOutput()) ?: trim($result->output());

            throw new RuntimeException('Bid.cars browser fetch failed: '.$error);
        }

        $envelope = json_decode(trim($result->output()), true);

        if (! is_array($envelope) || ($envelope['ok'] ?? false) !== true) {
            throw new RuntimeException('Bid.cars browser fetch failed: '.($envelope['error'] ?? 'unknown error'));
        }

        return (string) ($envelope['body'] ?? '');
    }

    private function fetchWithHttp(string $url, string $referer): string
    {
        $cookie = trim((string) config('services.bid_cars.cookie', ''));

        if ($cookie === '') {
            throw new RuntimeException(
                'Bid.cars requires a browser session. Install Playwright (npm install && npx playwright install chromium) or set BID_CARS_COOKIE in .env for manual testing.',
            );
        }

        try {
            $response = Http::withHeaders([
                'User-Agent' => self::USER_AGENT,
                'Accept' => '*/*',
                'Referer' => $referer,
                'X-Requested-With' => 'XMLHttpRequest',
                'Cookie' => $cookie,
            ])
                ->timeout(45)
                ->get($url);
        } catch (ConnectionException $e) {
            throw new InvalidArgumentException('Could not reach bid.cars: '.$e->getMessage(), previous: $e);
        }

        if (! $response->successful()) {
            throw new RuntimeException('Bid.cars returned HTTP '.$response->status().' for '.$url);
        }

        return $response->body();
    }
}