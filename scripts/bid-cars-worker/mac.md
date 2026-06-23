# Bid.cars scraper — Mac setup

Standalone worker: scrapes bid.cars in a browser and sends lots to the CARMAXING API. You do **not** need the full Laravel project on this Mac.

## What you need before starting

1. Clone or download this repo — `scripts/bid-cars-worker/` includes a ready-to-run **`import.config.json`** (backend, API key, brands, filters).
2. Node.js 20+ installed on the Mac.

## One-time setup on a new MacBook

### 1. Install Node.js

1. Open [https://nodejs.org](https://nodejs.org)
2. Download the **LTS** macOS installer
3. Run it and finish the wizard
4. Verify in Terminal:

```bash
node -v
npm -v
```

You should see version numbers (Node 20+ recommended).

### 2. Open the worker folder in Terminal

```bash
cd /path/to/bid-cars-worker
```

Replace `/path/to/bid-cars-worker` with the real path (e.g. `~/Downloads/bid-cars-worker`).

### 3. Install dependencies

```bash
npm install
npx playwright install chromium
```

First command installs Playwright. Second downloads Chromium (~100 MB, one time).

### 4. Allow the launcher to run (double-click option)

```bash
chmod +x pull-bid-cars.command
```

If macOS blocks the script: **System Settings → Privacy & Security → Open Anyway** (after first attempt).

---

## Run the scraper

### Option A — Double-click

1. Open Finder → `bid-cars-worker` folder
2. Double-click **`pull-bid-cars.command`**
3. Terminal opens, Chrome runs briefly, data uploads to the API
4. Press Enter when finished

### Option B — Terminal

```bash
cd /path/to/bid-cars-worker
BID_CARS_HEADLESS=0 node worker.mjs
```

Or:

```bash
npm run pull
```

(with `BID_CARS_HEADLESS=0` set if `headless` is `true` in config)

---

## What a successful run looks like

```
Collected 9 pages via one browser session.
  Audi page 1 -> 50 lots (first: 0-44767655)
  Audi page 2 -> 50 lots (first: 1-50629286)
  ...
Uploading to API...
Import run #12 -> completed
Fetched: 450
Created: 120
Updated: 330
```

---

## Run on a schedule (optional)

Daily at 8:00:

```bash
crontab -e
```

Add (adjust path):

```
0 8 * * * cd /Users/you/bid-cars-worker && BID_CARS_HEADLESS=0 /usr/local/bin/node worker.mjs >> pull.log 2>&1
```

Find your `node` path with `which node`.

---

## Troubleshooting

| Problem | Fix |
|--------|-----|
| `Node.js is not installed` | Install from nodejs.org, restart Terminal |
| `import.config.json is missing` | Re-clone the repo or restore the file from git |
| `apiKey` error | Set `apiKey` in `import.config.json` |
| `401` / `Invalid API key` | Check `apiKey` matches server `BID_CARS_IMPORT_API_KEY` |
| `503` / API not configured | Server missing `BID_CARS_IMPORT_API_KEY` in `.env` |
| Cloudflare / timeout / 403 | Set `"headless": false` in config; run again so Chrome is visible |
| `Load More` / timeout | Mac must stay awake; check internet; retry |
| Script won't double-click | `chmod +x pull-bid-cars.command`; check Privacy & Security |

After a good run, cookies are saved in **`.bid-cars-cookies.json`** in this folder. Do not delete if later runs should be faster — but delete it if scraping suddenly fails (forces a fresh browser session).

---

## Files in this folder

| File | Purpose |
|------|---------|
| `worker.mjs` | Main script (scrape + upload) |
| `session.mjs` | Browser session logic |
| `import.config.json` | Ready-to-run settings (backend, API key, brands, pages, filters) |
| `pull-bid-cars.command` | Double-click launcher |
| `.bid-cars-cookies.json` | Saved session (auto-created) |

---

## Tweaking settings

Edit **`import.config.json`** if you need to change brands, `pagesPerBrand` (number or `"full"`), filters, or the backend URL. Comments in the file explain each field.

## Security

- Keep the repo private if `import.config.json` contains your API key
- `.bid-cars-cookies.json` is auto-created locally and not committed
- This folder only **sends** data to your API; it does not need database credentials