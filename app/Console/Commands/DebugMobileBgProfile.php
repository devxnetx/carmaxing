<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\MobileBg\MobileBgClient;
use App\Services\MobileBg\MobileBgProfileScraper;
use App\Services\MobileBg\MobileBgProfileService;
use App\Services\MobileBg\MobileBgScraper;
use Illuminate\Console\Command;
use Throwable;

class DebugMobileBgProfile extends Command
{
    protected $signature = 'mobile-bg:debug-profile
                            {url : Dealer profile URL, e.g. https://icar.mobile.bg}
                            {--company= : Optional company ID to apply extracted data}';

    protected $description = 'Diagnose Mobile.bg profile extraction/import steps for a dealer URL';

    public function handle(
        MobileBgClient $client,
        MobileBgProfileScraper $profileScraper,
        MobileBgScraper $listingScraper,
        MobileBgProfileService $profileService,
    ): int {
        $url = (string) $this->argument('url');
        $companyId = $this->option('company');

        $this->line('URL: '.$url);
        $this->line('PHP: '.PHP_VERSION);
        $this->line('GD: '.(extension_loaded('gd') ? 'yes' : 'NO'));
        $this->line('WebP: '.(function_exists('imagewebp') ? 'yes' : 'NO'));
        $this->line('Queue: '.config('queue.default'));
        $this->line('MOBILE_BG_QUEUE: '.(env('MOBILE_BG_QUEUE') ?: '(default)'));
        $this->newLine();

        $steps = [
            'normalize_url' => fn () => $client->normalizeDealerUrl($url),
            'fetch_home' => fn () => strlen($client->get($client->normalizeDealerUrl($url).'/')),
            'fetch_about' => fn () => strlen($client->get($client->normalizeDealerUrl($url).'/about')),
            'fetch_contacts' => fn () => strlen($client->get($client->normalizeDealerUrl($url).'/contacts')),
            'scrape_profile' => function () use ($profileScraper, $url) {
                $profile = $profileScraper->scrape($url);

                return [
                    'sourceUrl' => $profile->sourceUrl,
                    'name' => $profile->name,
                    'phone' => $profile->phone,
                    'city' => $profile->city,
                    'regionName' => $profile->regionName,
                    'website' => $profile->website,
                    'logoUrl' => $profile->logoUrl,
                    'coverUrl' => $profile->coverUrl,
                ];
            },
            'collect_listing_refs' => fn () => [
                'count' => count($listingScraper->collectListingRefs($url)),
            ],
        ];

        if ($companyId) {
            $steps['apply_to_company'] = function () use ($profileService, $url, $companyId) {
                $company = Company::query()->findOrFail($companyId);
                $profile = $profileService->extractAndApply($company, $url);
                $company->refresh();

                return [
                    'company_id' => $company->id,
                    'name' => $company->name,
                    'phone' => $company->phone,
                    'region_id' => $company->region_id,
                    'mobile_bg_url' => $company->mobile_bg_url,
                    'profile_name' => $profile->name,
                ];
            };
        }

        foreach ($steps as $name => $step) {
            $this->components->task($name, function () use ($step, $name) {
                try {
                    $result = $step();

                    if (is_array($result)) {
                        $this->newLine();
                        $this->line(json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
                    } elseif (is_string($result) || is_int($result)) {
                        $this->newLine();
                        $this->line((string) $result);
                    }

                    return true;
                } catch (Throwable $exception) {
                    $this->newLine();
                    $this->error($exception::class.': '.$exception->getMessage());
                    $this->line($exception->getFile().':'.$exception->getLine());

                    return false;
                }
            });

            if ($this->output->isVerbose() === false && $this->output->isDecorated()) {
                // keep output compact between tasks
            }
        }

        return self::SUCCESS;
    }
}