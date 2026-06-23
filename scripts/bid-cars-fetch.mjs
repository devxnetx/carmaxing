import { chromium } from 'playwright';
import { mkdirSync, readFileSync, writeFileSync } from 'fs';
import { dirname, resolve } from 'path';
import { fileURLToPath } from 'url';

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

const referer = options.referer;

if (!referer) {
    console.log(JSON.stringify({
        ok: false,
        error: 'Usage: node bid-cars-fetch.mjs --referer=<search-results-page-url>',
    }));
    process.exit(1);
}

const userAgent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36';
const useHeadless = process.env.BID_CARS_HEADLESS !== '0';

let browser;

function loadStoredCookies() {
    try {
        const raw = readFileSync(cookiePath, 'utf8');

        return JSON.parse(raw);
    } catch {
        return null;
    }
}

function saveCookies(cookies) {
    mkdirSync(dirname(cookiePath), { recursive: true });
    writeFileSync(cookiePath, JSON.stringify(cookies, null, 2));
}

async function waitForCloudflare(page) {
    for (let attempt = 0; attempt < 60; attempt++) {
        const title = await page.title();

        if (!title.includes('Just a moment')) {
            return title;
        }

        await page.waitForTimeout(2_000);
    }

    throw new Error('Timed out waiting for Cloudflare challenge to clear.');
}

try {
    const launchOptions = {
        headless: useHeadless,
        args: ['--disable-blink-features=AutomationControlled'],
    };

    try {
        browser = await chromium.launch({ ...launchOptions, channel: 'chrome' });
    } catch {
        browser = await chromium.launch(launchOptions);
    }

    const context = await browser.newContext({
        userAgent,
        locale: 'en-US',
        viewport: { width: 1440, height: 900 },
        extraHTTPHeaders: {
            'Accept-Language': 'en-US,en;q=0.9',
        },
    });

    const storedCookies = loadStoredCookies();

    if (Array.isArray(storedCookies) && storedCookies.length > 0) {
        await context.addCookies(storedCookies);
    }

    await context.addInitScript(() => {
        Object.defineProperty(navigator, 'webdriver', {
            get: () => undefined,
        });
    });

    const page = await context.newPage();

    const responsePromise = page.waitForResponse(
        (response) => response.url().includes('/app/search/request'),
        { timeout: 180_000 },
    );

    await page.goto(referer, {
        waitUntil: 'domcontentloaded',
        timeout: 180_000,
    });

    const title = await waitForCloudflare(page);
    const apiResponse = await responsePromise;
    const status = apiResponse.status();
    const body = await apiResponse.text();

    if (status < 200 || status >= 300) {
        console.log(JSON.stringify({
            ok: false,
            error: `Bid.cars API returned HTTP ${status}`,
            page_title: title,
            api_url: apiResponse.url(),
            body: body.slice(0, 500),
        }));
        process.exit(1);
    }

    JSON.parse(body);
    saveCookies(await context.cookies());

    console.log(JSON.stringify({
        ok: true,
        status,
        body,
        transport: 'intercepted-search-page',
        api_url: apiResponse.url(),
        page_title: title,
        headless: useHeadless,
    }));
} catch (error) {
    console.log(JSON.stringify({
        ok: false,
        error: error instanceof Error ? error.message : String(error),
    }));
    process.exit(1);
} finally {
    if (browser) {
        await browser.close();
    }
}