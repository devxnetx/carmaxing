# CARMAXING

Bulgarian car classifieds platform built with Laravel 13. Inspired by mobile.bg search UX, with original design, social-only auth, company API, and BG/EN localization.

## Features

- **Social login only** — Google, Facebook, Apple (Laravel Socialite)
- **Onboarding wizard** — private person vs company/dealer
- **Private sellers** — phone only on listing page (no public profile)
- **Company profiles** — public dealer page with all listings
- **Search** — landing quick filters + extended search (features, series/model hierarchy like mobile.bg BMW 1 Series → 118, 120…)
- **Listing detail** — specs, features, gallery, contact, similar ads
- **Day / night mode** — light default, persisted per user
- **BG / EN** — Bulgarian default, switchable
- **Dealer API** — API keys in settings, CRUD + archive (no hard delete), OpenAPI docs at `/docs/api`

## Requirements

- PHP 8.2+
- Composer
- Node.js 20.19+ (for Vite 8)
- SQLite (default) or MySQL/PostgreSQL

## Setup

```bash
cd autoclasi
composer install
cp .env.example .env   # if needed
php artisan key:generate
php artisan migrate:fresh --seed
npm install --legacy-peer-deps
npm run build
php artisan serve
```

Visit http://localhost:8000

## Background jobs (Mobile.bg import, email alerts)

Imports and other tasks use Laravel's queue. For **local development**, `.env.example` sets `QUEUE_CONNECTION=sync` so imports run immediately when you click the button.

For **production** with `QUEUE_CONNECTION=database`, run a persistent **background process** on Laravel Cloud:

```bash
php artisan queue:work database --sleep=3 --tries=1 --timeout=1800
```

Mobile.bg imports allow up to 30 minutes (`ImportMobileBgListings::$timeout = 1800`).

Enable the Laravel Cloud **Scheduler** separately for cron tasks only (`searches:notify` hourly, `tenders:close-expired` every minute) — not for `queue:work` when a background process is already running.

## Social auth credentials

Add to `.env`:

```
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
FACEBOOK_CLIENT_ID=
FACEBOOK_CLIENT_SECRET=
APPLE_CLIENT_ID=
APPLE_CLIENT_SECRET=
```

Redirect URIs: `{APP_URL}/auth/{provider}/callback`

## Dealer API

1. Register as company → complete onboarding
2. Settings → Generate API key
3. Use `Authorization: Bearer ac_...` or `X-API-Key: ac_...`
4. Docs: `/docs/api` and `/docs/openapi.yaml`

## Catalog data

Brands/models and features are seeded from `database/data/`. Extend `CatalogSeeder` or add JSON importers for a full mobile.bg-scale catalog.

## Project structure

```
app/
  Enums/           AccountType, ListingStatus
  Http/Controllers/  Web + Api
  Models/          User, Company, Listing, VehicleBrand, VehicleModel, ...
  Services/        ListingSearchService
database/
  data/            regions.json, vehicle_features.json
  seeders/         CatalogSeeder, DemoSeeder
lang/bg|en/        UI strings
resources/views/   Blade + Alpine.js + Tailwind 4
routes/api.php     Dealer API v1
```

## Laravel Cloud deploy

`public/build` is committed so deploys work even before frontend build commands are configured. For the proper setup, add these **Build commands** in your environment → Settings → Deployments:

```bash
composer install --no-dev
npm ci
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

**Deploy commands:**

```bash
php artisan migrate --force
```

After that works, you can add `/public/build` back to `.gitignore` and let Cloud build assets on each deploy.

## Performance & SEO

- System font stack (no webfont downloads)
- CSS in `<head>`, JS deferred at end of `<body>`
- Lazy-loaded listing images with explicit dimensions (CLS-safe)
- SVG favicon (~450B) + PNG fallbacks
- Compact JSON-LD, `/sitemap.xml`, `robots.txt`
- Production: run `php artisan config:cache && php artisan route:cache && php artisan view:cache`

Regenerate raster icons after logo changes: `php scripts/generate-icons.php`

## Demo data

After seeding:
- Company demo: iCar (Porsche Cayenne listing)
- Private demo: BMW X3 listing
- 28+ brands with hierarchical BMW models