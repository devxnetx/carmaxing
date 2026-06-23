import { readFileSync, existsSync } from 'fs';
import { resolve } from 'path';
import { fileURLToPath } from 'url';
import { dirname } from 'path';
import { runBidCarsSession } from './session.mjs';
import { loadWorkerConfig } from './config.mjs';

const workerDir = dirname(fileURLToPath(import.meta.url));
const legacyConfigPath = resolve(workerDir, process.env.BID_CARS_CONFIG || 'config.json');
const cookiePath = resolve(workerDir, '.bid-cars-cookies.json');

function loadLocalOverrides() {
    if (!existsSync(legacyConfigPath)) {
        return {};
    }

    return JSON.parse(readFileSync(legacyConfigPath, 'utf8'));
}

async function pushToApi(config, session) {
    const response = await fetch(config.apiUrl, {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-API-Key': config.apiKey,
        },
        body: JSON.stringify({
            brands: session.brands,
            pages_per_brand: session.pages_per_brand,
            pages: session.pages,
        }),
    });

    const body = await response.text();
    let json;

    try {
        json = JSON.parse(body);
    } catch {
        throw new Error(`API returned non-JSON response (HTTP ${response.status}): ${body.slice(0, 300)}`);
    }

    if (!response.ok) {
        throw new Error(`API import failed (HTTP ${response.status}): ${json.message ?? body}`);
    }

    return json;
}

function printSessionSummary(session) {
    console.log(`Collected ${session.collected_pages} pages via one browser session.`);

    for (const page of session.pages) {
        const firstLot = page.items?.[0]?.lot ?? 'n/a';
        console.log(`  ${page.brand} page ${page.current_page} -> ${page.items?.length ?? 0} lots (first: ${firstLot})`);
    }
}

async function main() {
    const config = loadWorkerConfig(loadLocalOverrides());

    if (!config.apiUrl) {
        throw new Error('import.config.json must include backendDomain (or apiUrl).');
    }

    if (!config.apiKey) {
        throw new Error('import.config.json must include apiKey (or set BID_CARS_IMPORT_API_KEY).');
    }

    console.log('Bid.cars worker starting...');
    console.log(`Import config: ${config.importConfigPath}`);
    console.log(`Backend: ${config.backendDomain}`);
    console.log(`API: ${config.apiUrl}`);
    console.log(`Brands: ${config.brands.join(', ')}`);
    console.log(`Pages per brand: ${config.pagesPerBrand === 'full' ? 'full (until no more)' : config.pagesPerBrand}`);
    console.log(`Headless: ${config.headless ? 'yes' : 'no'}`);
    console.log('');

    const session = await runBidCarsSession({
        brands: config.brands,
        pagesPerBrand: config.pagesPerBrand,
        headless: config.headless,
        cookiePath,
        filters: config.filters,
    });

    printSessionSummary(session);
    console.log('');
    console.log('Uploading to API...');

    const result = await pushToApi(config, session);

    console.log('');
    console.log(`Import run #${result.import_run_id} -> ${result.status}`);
    console.log(`Fetched: ${result.total_fetched}`);
    console.log(`Created: ${result.created_count}`);
    console.log(`Updated: ${result.updated_count}`);
}

main().catch((error) => {
    console.error(error instanceof Error ? error.message : String(error));
    process.exit(1);
});