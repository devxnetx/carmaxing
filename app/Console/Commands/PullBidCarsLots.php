<?php

namespace App\Console\Commands;

use App\Models\AuctionLot;
use App\Services\BidCars\BidCarsSearchImporter;
use App\Support\BidCarsImportConfig;
use Illuminate\Console\Command;
use Throwable;

class PullBidCarsLots extends Command
{
    protected $signature = 'bid-cars:pull
                            {--brands= : Comma-separated makes (default: import.config.json)}
                            {--pages= : Pages per brand, or "full" to load until no more (default: import.config.json)}
                            {--probe-per-page : Test whether bid.cars honors a larger per_page value}';

    protected $description = 'Pull bid.cars auction lots into the separate auction_lots table';

    public function handle(BidCarsSearchImporter $importer): int
    {
        $brands = $this->option('brands') !== null
            ? array_values(array_filter(array_map(trim(...), explode(',', (string) $this->option('brands')))))
            : BidCarsImportConfig::brands();

        $pages = $this->option('pages') !== null
            ? BidCarsImportConfig::normalizePagesPerBrand($this->option('pages'))
            : BidCarsImportConfig::pagesPerBrand();

        $probePerPage = (bool) $this->option('probe-per-page');

        if ($brands === []) {
            $this->error('At least one brand is required.');

            return self::FAILURE;
        }

        $this->line('Config: '.BidCarsImportConfig::path());
        $this->line('Brands: '.implode(', ', $brands));
        $this->line('Pages per brand: '.BidCarsImportConfig::formatPagesPerBrand($pages));
        $this->line('Probe per_page: '.($probePerPage ? 'yes' : 'no'));
        $this->newLine();

        try {
            $result = $importer->importBrands(
                brands: $brands,
                pagesPerBrand: $pages,
                perPage: null,
                probePerPage: $probePerPage,
            );
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $run = $result['run'];

        if (($result['page_summaries'] ?? []) !== []) {
            $this->info('Collected pages:');
            foreach ($result['page_summaries'] as $summary) {
                $this->line(sprintf(
                    '  %s page %d -> %d lots (first: %s)',
                    $summary['brand'],
                    $summary['page'],
                    $summary['count'],
                    $summary['first_lot'] ?? 'n/a',
                ));
            }

            $this->newLine();
        }

        $this->info('Import run #'.$run->id.' finished with status: '.$run->status);
        $this->line('Fetched: '.$run->total_fetched);
        $this->line('Created: '.$run->created_count);
        $this->line('Updated: '.$run->updated_count);

        $this->newLine();
        $this->info('Lots by brand:');

        foreach ($brands as $brand) {
            $count = AuctionLot::query()
                ->whereHas('brand', fn ($query) => $query->where('name', $brand))
                ->count();

            $this->line("  {$brand}: {$count}");
        }

        $sample = AuctionLot::query()
            ->with(['brand', 'model'])
            ->latest('id')
            ->first();

        if ($sample) {
            $this->newLine();
            $this->info('Latest lot saved:');
            $this->line('  '.$sample->external_lot.' | '.$sample->title);
            $this->line('  Brand/model: '.($sample->brand?->name ?? 'n/a').' / '.($sample->model?->name ?? 'n/a'));
            $this->line('  Status: '.$sample->search_status.' | Est: $'.$sample->estimated_min_usd.' - $'.$sample->estimated_max_usd);
        }

        if (is_array($run->errors) && $run->errors !== []) {
            $this->newLine();
            $this->warn('Non-fatal page warnings:');
            foreach ($run->errors as $error) {
                $this->line('  - '.json_encode($error, JSON_UNESCAPED_UNICODE));
            }
        }

        return $run->status === 'failed' ? self::FAILURE : self::SUCCESS;
    }
}