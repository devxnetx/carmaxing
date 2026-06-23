import { dirname, resolve } from 'path';
import { fileURLToPath } from 'url';
import { runBidCarsSession } from './bid-cars-worker/session.mjs';
import { loadImportConfig, parsePagesPerBrand } from './bid-cars-worker/config.mjs';

const rootDir = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const cookiePath = resolve(rootDir, 'storage/app/bid-cars-cookies.json');

const args = process.argv.slice(2);
const options = Object.fromEntries(
    args
        .filter((arg) => arg.startsWith('--'))
        .map((arg) => {
            const [key, ...rest] = arg.replace(/^--/, '').split('=');

            return [key, rest.join('=')];
        }),
);

const importSettings = loadImportConfig();
const brands = options.brands
    ? options.brands.split(',').map((b) => b.trim()).filter(Boolean)
    : importSettings.brands;
const pagesPerBrand = options.pages
    ? parsePagesPerBrand(options.pages, importSettings.pagesPerBrand)
    : importSettings.pagesPerBrand;
const headless = process.env.BID_CARS_HEADLESS !== '0' && importSettings.headless;

try {
    const session = await runBidCarsSession({
        brands,
        pagesPerBrand,
        headless,
        cookiePath,
        filters: importSettings.filters,
    });

    const pages = session.pages.map((page) => ({
        brand: page.brand,
        current_page: page.current_page,
        payload: {
            current_page: page.current_page,
            data: page.items,
        },
    }));

    console.log(JSON.stringify({
        ok: true,
        brands: session.brands,
        pages_per_brand: session.pages_per_brand,
        collected_pages: session.collected_pages,
        pages,
        headless: session.headless,
    }));
} catch (error) {
    console.log(JSON.stringify({
        ok: false,
        error: error instanceof Error ? error.message : String(error),
    }));
    process.exit(1);
}