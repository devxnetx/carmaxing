import { chromium } from 'playwright';
import { readFileSync, writeFileSync } from 'fs';

const cookies = JSON.parse(readFileSync('storage/app/bid-cars-cookies.json', 'utf8'));
const referer = 'https://bid.cars/en/search/results?search-type=filters&status=All&type=Automobile&make=BMW&model=All&year-from=2005&year-to=2027&auction-type=All&start-code=Run%20and%20Drive&transmission=Automatic&engine-hp-from=148&estimated-min=4500';
const browser = await chromium.launch({ headless: false, channel: 'chrome' });
const context = await browser.newContext({ viewport: { width: 1440, height: 2000 } });
await context.addCookies(cookies);
const page = await context.newPage();
const resp = page.waitForResponse((r) => r.url().includes('/app/search/request'));
await page.goto(referer, { waitUntil: 'domcontentloaded', timeout: 120000 });
await resp;
await page.waitForTimeout(3000);
await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
await page.waitForTimeout(2000);
writeFileSync('/tmp/bidcars-search.html', await page.content());
console.log('saved');
await browser.close();