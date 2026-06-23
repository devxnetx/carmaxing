# Bid.cars scraper — Windows setup

Standalone worker: scrapes bid.cars in a browser and sends lots to the CARMAXING API. You do **not** need the full Laravel project on this PC.

## What you need before starting

1. Clone or download this repo — `scripts/bid-cars-worker/` includes a ready-to-run **`import.config.json`** (backend, API key, brands, filters).
2. Node.js 20+ installed on the PC.

## One-time setup on a new Windows PC

### 1. Install Node.js

1. Open [https://nodejs.org](https://nodejs.org)
2. Download the **LTS** Windows installer (.msi)
3. Run it — leave **“Add to PATH”** checked
4. Close and reopen **Command Prompt** or **PowerShell**
5. Verify:

```bat
node -v
npm -v
```

You should see version numbers (Node 20+ recommended).

### 2. Open the worker folder

In Command Prompt:

```bat
cd C:\path\to\bid-cars-worker
```

Or in File Explorer: go to the folder, type `cmd` in the address bar, press Enter.

### 3. Install dependencies

```bat
npm install
npx playwright install chromium
```

First command installs Playwright. Second downloads Chromium (~100 MB, one time).

If `npx playwright install chromium` fails, try:

```bat
npx playwright install chrome
```

(Uses installed Google Chrome instead of bundled Chromium.)

---

## Run the scraper

### Option A — Double-click

1. Open the `bid-cars-worker` folder in File Explorer
2. Double-click **`pull-bid-cars.bat`**
3. A console window opens, Chrome runs briefly, data uploads to the API
4. Press any key when finished

### Option B — Command Prompt

```bat
cd C:\path\to\bid-cars-worker
set BID_CARS_HEADLESS=0
node worker.mjs
```

Or:

```bat
npm run pull
```

(with `set BID_CARS_HEADLESS=0` if `headless` is `true` in config)

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

1. Open **Task Scheduler**
2. Create Basic Task → Daily → choose time
3. Action: **Start a program**
4. Program: full path to `node.exe` (e.g. `C:\Program Files\nodejs\node.exe`)
5. Arguments: `worker.mjs`
6. Start in: `C:\path\to\bid-cars-worker`
7. Add environment variable `BID_CARS_HEADLESS=0` if needed (or set `"headless": false` in config)

---

## Troubleshooting

| Problem | Fix |
|--------|-----|
| `'node' is not recognized` | Reinstall Node.js with “Add to PATH”; reopen terminal |
| `import.config.json is missing` | Re-clone the repo or restore the file from git |
| `apiKey` error | Set `apiKey` in `import.config.json` |
| `401` / `Invalid API key` | Check `apiKey` matches server `BID_CARS_IMPORT_API_KEY` |
| `503` / API not configured | Server missing `BID_CARS_IMPORT_API_KEY` in `.env` |
| Cloudflare / timeout / 403 | Set `"headless": false` in config; run again with visible Chrome |
| `Load More` / timeout | PC must stay awake; check internet; retry |
| SmartScreen blocks `.bat` | Click **More info → Run anyway** (you trust this script) |
| npm errors | Run Command Prompt as normal user from the worker folder, not as wrong directory |

After a good run, cookies are saved in **`.bid-cars-cookies.json`** in this folder. Do not delete if later runs should be faster — but delete it if scraping suddenly fails (forces a fresh browser session).

---

## Mac vs Windows — same requirements

Both platforms need:

- Node.js
- `npm install` (once per folder)
- `npx playwright install chromium` (once per machine)
- `import.config.json` with backend domain + API key

The only difference is the launcher: **`.command`** on Mac, **`.bat`** on Windows.

---

## Files in this folder

| File | Purpose |
|------|---------|
| `worker.mjs` | Main script (scrape + upload) |
| `session.mjs` | Browser session logic |
| `import.config.json` | Ready-to-run settings (backend, API key, brands, pages, filters) |
| `pull-bid-cars.bat` | Double-click launcher |
| `.bid-cars-cookies.json` | Saved session (auto-created) |

---

## Tweaking settings

Edit **`import.config.json`** if you need to change brands, `pagesPerBrand` (number or `"full"`), filters, or the backend URL. Comments in the file explain each field.

## Security

- Keep the repo private if `import.config.json` contains your API key
- `.bid-cars-cookies.json` is auto-created locally and not committed
- This folder only **sends** data to your API; it does not need database credentials