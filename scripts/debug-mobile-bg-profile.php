<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\User;
use App\Services\MobileBg\MobileBgClient;
use App\Services\MobileBg\MobileBgProfileScraper;
use App\Services\MobileBg\MobileBgProfileService;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$url = $argv[1] ?? 'https://ratola.mobile.bg/';
$companyId = isset($argv[2]) ? (int) $argv[2] : null;

echo "=== Mobile.bg profile debug ===\n";
echo "URL: {$url}\n";
if ($companyId) {
    echo "Company ID: {$companyId}\n";
}
echo 'PHP: '.PHP_VERSION."\n";
echo 'GD: '.(extension_loaded('gd') ? 'yes' : 'NO')."\n";
echo 'WebP: '.(function_exists('imagewebp') ? 'yes' : 'NO')."\n";
echo 'Storage public writable: '.(is_writable(storage_path('app/public')) ? 'yes' : 'NO')."\n";
echo 'AWS_BUCKET: '.(env('AWS_BUCKET') ?: '(empty)')."\n\n";

$client = app(MobileBgClient::class);
$scraper = app(MobileBgProfileScraper::class);
$service = app(MobileBgProfileService::class);

$steps = [
    'normalize_url' => function () use ($client, $url) {
        return $client->normalizeDealerUrl($url);
    },
    'fetch_home' => function () use ($client, $url) {
        $base = $client->normalizeDealerUrl($url);

        return strlen($client->get($base.'/'));
    },
    'fetch_about' => function () use ($client, $url) {
        $base = $client->normalizeDealerUrl($url);

        return strlen($client->get($base.'/about'));
    },
    'fetch_contacts' => function () use ($client, $url) {
        $base = $client->normalizeDealerUrl($url);

        return strlen($client->get($base.'/contacts'));
    },
    'scrape_profile' => function () use ($scraper, $url) {
        $profile = $scraper->scrape($url);

        return json_encode([
            'sourceUrl' => $profile->sourceUrl,
            'name' => $profile->name,
            'description_len' => $profile->description ? strlen($profile->description) : 0,
            'phone' => $profile->phone,
            'address' => $profile->address,
            'city' => $profile->city,
            'regionName' => $profile->regionName,
            'website' => $profile->website,
            'logoUrl' => $profile->logoUrl,
            'coverUrl' => $profile->coverUrl,
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    },
    'collect_listing_refs' => function () use ($url) {
        $refs = app(\App\Services\MobileBg\MobileBgScraper::class)->collectListingRefs($url);

        return json_encode([
            'count' => count($refs),
            'first_three' => array_slice($refs, 0, 3),
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    },
    'apply_to_company' => function () use ($service, $url, $companyId) {
        $company = $companyId
            ? Company::query()->find($companyId)
            : Company::query()->first();

        if (! $company) {
            $user = User::query()->create([
                'name' => 'Debug User',
                'email' => 'debug-'.time().'@example.test',
                'phone' => '+359888000000',
                'account_type' => \App\Enums\AccountType::Company,
                'onboarding_completed_at' => now(),
                'email_verified_at' => now(),
            ]);

            $company = Company::query()->create([
                'user_id' => $user->id,
                'name' => 'Debug Company',
                'slug' => 'debug-company-'.time(),
                'phone' => '+359888000000',
            ]);
        }

        $profile = $service->extractAndApply($company, $url);
        $company->refresh();

        return json_encode([
            'company_id' => $company->id,
            'name' => $company->name,
            'phone' => $company->phone,
            'logo' => $company->logo,
            'cover_image' => $company->cover_image,
            'mobile_bg_url' => $company->mobile_bg_url,
            'website' => $company->website,
            'region_id' => $company->region_id,
            'profile_name' => $profile->name,
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    },
];

$failed = false;

foreach ($steps as $name => $step) {
    echo "--- {$name} ---\n";

    try {
        $result = $step();
        echo "OK\n";
        if (is_string($result)) {
            echo $result."\n";
        }
    } catch (Throwable $e) {
        $failed = true;
        echo "FAIL: ".$e::class.': '.$e->getMessage()."\n";
        echo 'File: '.$e->getFile().':'.$e->getLine()."\n";
        if ($e->getPrevious()) {
            echo 'Previous: '.$e->getPrevious()::class.': '.$e->getPrevious()->getMessage()."\n";
        }
        echo "\nTrace (first 8 frames):\n";
        foreach (array_slice($e->getTrace(), 0, 8) as $i => $frame) {
            $where = ($frame['file'] ?? '?').':'.($frame['line'] ?? '?');
            $fn = ($frame['class'] ?? '').($frame['type'] ?? '').($frame['function'] ?? '');
            echo "  #{$i} {$where} {$fn}\n";
        }
        break;
    }

    echo "\n";
}

exit($failed ? 1 : 0);