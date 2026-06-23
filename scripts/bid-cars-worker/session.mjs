import { chromium } from 'playwright';
import { mkdirSync, readFileSync, writeFileSync } from 'fs';
import { dirname } from 'path';
import { FULL_PAGES, MAX_PAGES_PER_BRAND } from './config.mjs';

async function waitForCloudflare(page) {
    for (let attempt = 0; attempt < 60; attempt++) {
        if (!(await page.title()).includes('Just a moment')) {
            return;
        }

        await page.waitForTimeout(2_000);
    }

    throw new Error('Timed out waiting for Cloudflare challenge to clear.');
}

async function waitForResultsReady(page) {
    await page.locator('.loader.active').waitFor({ state: 'hidden', timeout: 120_000 }).catch(() => {});
    await page.waitForTimeout(800);
}

function waitForNewSearchResponse(page, seenKeys, timeoutMs = 60_000) {
    return new Promise((resolve, reject) => {
        const timeout = setTimeout(() => {
            page.off('response', handler);
            reject(new Error('Timed out waiting for the next search page response.'));
        }, timeoutMs);

        const handler = async (response) => {
            if (!response.url().includes('/app/search/request') || response.status() !== 200) {
                return;
            }

            try {
                const json = JSON.parse(await response.text());
                const key = `${json.current_page}:${json.data?.[0]?.lot ?? ''}:${json.data?.length ?? 0}`;

                if (seenKeys.has(key)) {
                    return;
                }

                seenKeys.add(key);
                clearTimeout(timeout);
                page.off('response', handler);
                resolve(json);
            } catch {
                // Ignore malformed responses and keep waiting.
            }
        };

        page.on('response', handler);
    });
}

function buildSearchUrl(make, filters) {
    const params = new URLSearchParams({ ...filters, make });

    return `https://bid.cars/en/search/results?${params.toString()}`;
}

function loadStoredCookies(cookiePath) {
    try {
        return JSON.parse(readFileSync(cookiePath, 'utf8'));
    } catch {
        return null;
    }
}

function saveCookies(cookiePath, cookies) {
    mkdirSync(dirname(cookiePath), { recursive: true });
    writeFileSync(cookiePath, JSON.stringify(cookies, null, 2));
}

async function hasLoadMore(page) {
    const loadMore = page.locator('.breadcrumbs.load-more a.btn-primary').first();

    if (await loadMore.count() === 0) {
        return false;
    }

    return loadMore.isVisible();
}

async function loadNextPage(page, seenKeys) {
    const loadMore = page.locator('.breadcrumbs.load-more a.btn-primary').first();
    await loadMore.waitFor({ state: 'visible', timeout: 10_000 });
    await loadMore.scrollIntoViewIfNeeded();

    const nextPayloadPromise = waitForNewSearchResponse(page, seenKeys);
    await loadMore.click();
    await waitForResultsReady(page);

    return nextPayloadPromise;
}

async function collectBrand(page, brand, pagesPerBrand, filters) {
    const collected = [];
    const seenKeys = new Set();
    const isFull = pagesPerBrand === FULL_PAGES;
    const searchUrl = buildSearchUrl(brand, filters);

    const firstResponse = page.waitForResponse(
        (response) => response.url().includes('/app/search/request') && response.status() === 200,
        { timeout: 180_000 },
    );

    await page.goto(searchUrl, { waitUntil: 'domcontentloaded', timeout: 180_000 });
    await waitForCloudflare(page);

    const firstPayload = JSON.parse(await (await firstResponse).text());
    const firstKey = `${firstPayload.current_page}:${firstPayload.data?.[0]?.lot ?? ''}:${firstPayload.data?.length ?? 0}`;
    seenKeys.add(firstKey);
    collected.push({
        brand,
        current_page: firstPayload.current_page,
        items: firstPayload.data ?? [],
    });

    await waitForResultsReady(page);

    let pageIndex = 2;

    while (true) {
        if (!isFull && pageIndex > pagesPerBrand) {
            break;
        }

        if (isFull && pageIndex > MAX_PAGES_PER_BRAND) {
            break;
        }

        if (!(await hasLoadMore(page))) {
            break;
        }

        try {
            const payload = await loadNextPage(page, seenKeys);

            if (!Array.isArray(payload.data) || payload.data.length === 0) {
                break;
            }

            collected.push({
                brand,
                current_page: payload.current_page,
                items: payload.data,
            });
        } catch {
            break;
        }

        pageIndex++;
    }

    return collected;
}

const userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36';

/**
 * @param {{ brands: string[], pagesPerBrand: number | 'full', headless?: boolean, cookiePath: string, filters: Record<string, string|number> }} options
 */
export async function runBidCarsSession({ brands, pagesPerBrand, headless = true, cookiePath, filters }) {
    let browser;

    try {
        const launchOptions = {
            headless,
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
            viewport: { width: 1440, height: 1200 },
            extraHTTPHeaders: {
                'Accept-Language': 'en-US,en;q=0.9',
            },
        });

        const storedCookies = loadStoredCookies(cookiePath);

        if (Array.isArray(storedCookies) && storedCookies.length > 0) {
            await context.addCookies(storedCookies);
        }

        await context.addInitScript(() => {
            Object.defineProperty(navigator, 'webdriver', {
                get: () => undefined,
            });
        });

        const page = await context.newPage();
        const allPages = [];

        for (const brand of brands) {
            const brandPages = await collectBrand(page, brand, pagesPerBrand, filters);
            allPages.push(...brandPages);
            await page.waitForTimeout(1_000);
        }

        saveCookies(cookiePath, await context.cookies());

        return {
            brands,
            pages_per_brand: pagesPerBrand,
            collected_pages: allPages.length,
            pages: allPages,
            headless,
            filters,
        };
    } finally {
        if (browser) {
            await browser.close();
        }
    }
}