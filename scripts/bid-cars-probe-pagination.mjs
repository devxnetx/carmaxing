import { chromium } from 'playwright';
import { readFileSync } from 'fs';
import { resolve, dirname } from 'path';
import { fileURLToPath } from 'url';

const rootDir = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const cookiePath = resolve(rootDir, 'storage/app/bid-cars-cookies.json');
const referer = 'https://bid.cars/en/search/results?search-type=filters&status=All&type=Automobile&make=BMW&model=All&year-from=2005&year-to=2027&auction-type=All&start-code=Run%20and%20Drive&transmission=Automatic&engine-hp-from=148&estimated-min=4500';
const apiBase = 'https://bid.cars/app/search/request?search-type=filters&status=All&type=Automobile&make=BMW&model=All&year-from=2005&year-to=2027&auction-type=All&start-code=Run%20and%20Drive&transmission=Automatic&engine-hp-from=148&estimated-min=4500';

async function waitForCloudflare(page) {
    for (let i = 0; i < 60; i++) {
        if (!(await page.title()).includes('Just a moment')) return;
        await page.waitForTimeout(2000);
    }
    throw new Error('CF timeout');
}

const browser = await chromium.launch({ headless: false, channel: 'chrome' });
const context = await browser.newContext({ viewport: { width: 1440, height: 1200 } });
try { await context.addCookies(JSON.parse(readFileSync(cookiePath, 'utf8'))); } catch {}
const page = await context.newPage();

const responsePromise = page.waitForResponse(r => r.url().includes('/app/search/request'), { timeout: 180000 });
await page.goto(referer, { waitUntil: 'domcontentloaded', timeout: 180000 });
await waitForCloudflare(page);
const page1 = JSON.parse(await (await responsePromise).text());

const inBrowserFetch = await page.evaluate(async (base) => {
    const out = [];
    for (const pageNum of [2, 3]) {
        const res = await fetch(`${base}&page=${pageNum}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: '*/*' },
            credentials: 'same-origin',
        });
        const json = await res.json();
        out.push({
            page: pageNum,
            status: res.status,
            current_page: json.current_page,
            first_lot: json.data?.[0]?.lot ?? null,
        });
    }
    return out;
}, apiBase);

const contextRequest = [];
for (const pageNum of [2, 3]) {
    const res = await context.request.get(`${apiBase}&page=${pageNum}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest', Referer: referer },
    });
    const json = await res.json();
    contextRequest.push({
        page: pageNum,
        status: res.status(),
        current_page: json.current_page,
        first_lot: json.data?.[0]?.lot ?? null,
    });
}

console.log(JSON.stringify({
    page1: { current_page: page1.current_page, first_lot: page1.data?.[0]?.lot },
    inBrowserFetch,
    contextRequest,
}, null, 2));
await browser.close();