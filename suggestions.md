# AutoClasi — Feature Ideas & Best Practices

Research notes from major international car marketplaces (AutoScout24, mobile.de, Autotrader, CarGurus, Cars.com) and what could make sense for a Bulgarian platform like AutoClasi.

**Last updated:** June 2026

---

## How to read this document

| Priority | Meaning |
|----------|---------|
| **P1** | High impact, reasonable effort — good next steps |
| **P2** | Strong differentiator, more work or partnerships |
| **P3** | Nice-to-have or long-term |

---

## What the best international sites do well

### 1. Search & discovery (you already have a solid base)

**What they do:**
- **Saved searches + email/push alerts** when new cars match filters (AutoScout24, mobile.de, Autotrader)
- **Sort by relevance**, not only price/year — “best match”, “good deal”, “near you”
- **Map view** for dealers and private sellers in a radius
- **Compare listings** side-by-side (2–4 cars)
- **Recently viewed** and “continue where you left off”
- **Smart defaults** — e.g. hide archived/sold, boost listings with photos

**AutoClasi ideas:**
- **P1** Saved search + email alert (big retention win; users come back without opening the site daily)
- **P1** “Similar listings” you already have — extend with **price vs market** hint
- **P2** Map view (Bulgaria + abroad filter you added fits this well)
- **P2** Compare up to 3 listings in a sticky tray

---

### 2. Trust & transparency (where Bulgaria needs the most help)

**What they do:**
- **Verified dealer** badge (company reg, address, phone verified)
- **Listing quality score** — min photos, description length, VIN present, price filled
- **“How long on market”** — days since published / price changes
- **Price history** — “was €12,500, now €11,900”
- **Report listing** — scam, wrong info, duplicate
- **Response time** badge for dealers (“replies within 2h”)
- **Video / 360° tour** badges in search results

**AutoClasi ideas:**
- **P1** Dealer verification tier (manual review → verified badge on company profile)
- **P1** Show **published date** and **last price change** on detail page
- **P1** Require or strongly encourage **VIN** for dealer listings (badge: “VIN provided”)
- **P2** Price drop notifications for favorited listings
- **P2** Public price history table (store changes in DB on each edit)

---

### 3. Vehicle history & VIN (your idea — detailed below)

**What they do:**
- **Carfax / AutoCheck** (US), **carVertical / autoDNA** (EU) — buy report or dealer includes it
- **Free VIN decode** — make, model, year, engine from VIN (specs only, not history)
- **Import history** highlighted for EU cars (country of first registration, mileage timeline)
- **“No accidents reported”** only when data exists — never fake green ticks

**AutoClasi ideas:**
- **P1** VIN field + **free basic decode** (make/model/year validation against listing)
- **P1** “Check full history” CTA → partner (affiliate) or paid add-on
- **P2** Dealer premium package: “includes carVertical report”
- **P3** White-label embedded report via B2B API

See **[VIN & history APIs](#vin--vehicle-history-apis)** section.

---

### 4. Pricing intelligence

**What they do:**
- **Market value estimate** — “€2,300 below similar cars”
- **Deal rating** — great / good / fair / high (CarGurus-style)
- **Financing calculator** — monthly payment from price, down payment, term
- **Leasing calculator** (mobile.de)
- **TCO hints** — fuel, tax, insurance ballpark

**AutoClasi ideas:**
- **P1** Simple **financing calculator** on listing page (no bank integration needed at first)
- **P2** “Average price for this model/year/mileage” from your own sold/active listings
- **P3** Partner with local bank / leasing for lead-gen (monetization)

---

### 5. Dealer & seller tools

**What they do:**
- **Bulk import / feed API** (you have mobile.bg import — good start)
- **Inventory dashboard** — views, leads, conversion per ad
- **Lead CRM** — inquiries in one inbox, mark read/replied
- **Auto-refresh / bump** paid feature
- **Featured / top ads** slots
- **Multi-user** dealer accounts (salesperson roles)
- **Stock sync** from DMS or Excel

**AutoClasi ideas:**
- **P1** Per-listing **stats**: views, favorites, inquiries (dealers love this)
- **P1** Inquiry inbox improvements — templates, mark as answered
- **P2** Paid **featured listing** / homepage carousel
- **P2** Scheduled **re-publish** or bump (paid)
- **P2** More import sources (automobile.bg, dealer XML feed)

---

### 6. Buyer engagement

**What they do:**
- **Favorites** (you have this)
- **Share listing** WhatsApp / Viber / copy link
- **Print-friendly** ad sheet for showroom visits
- **Schedule test drive** or “request callback”
- **Chat** in-app (high cost; many use phone/WhatsApp in BG anyway)
- **Buyer guides** — “how to buy used car”, import from DE, etc.

**AutoClasi ideas:**
- **P1** One-click **WhatsApp** message with pre-filled listing link + ad number
- **P1** **PDF export** of listing (for banks or family)
- **P2** Content hub / blog for SEO (“внос от Германия”, “как да провериш километри”)
- **P3** In-app chat (only if you have moderation capacity)

---

### 7. SEO & content (long-term traffic)

**What they do:**
- Brand + model landing pages (“BMW 320d обяви”)
- Dealer directory by city
- Structured data (you have JSON-LD — keep extending)
- Human + XML sitemap (you added this)

**AutoClasi ideas:**
- **P1** Auto-generated **brand/model** landing pages with filters pre-applied
- **P1** **Dealer directory** page per oblast
- **P2** “Cars under €X” / “New arrivals this week” curated pages

---

### 8. Mobile & performance

**What they do:**
- PWA or native apps with push for saved search
- Lazy images, WebP, LCP under 2.5s
- Bottom nav on mobile

**AutoClasi ideas:**
- **P1** **PWA** + push for saved search alerts (cheaper than native app)
- **P1** Continue optimizing images (you already lazy-load)

---

## Suggested roadmap for AutoClasi

### Phase 1 — Trust & retention (1–2 months)
1. Saved search + email alerts  
2. Listing stats for sellers (views, favorites)  
3. VIN field validation + free decode (specs match listing)  
4. “Check history” partner link (affiliate, no API cost)  
5. WhatsApp share on listing page  
6. Published date + optional price history  

### Phase 2 — Monetization & dealers (2–4 months)
1. Featured / promoted listings  
2. Dealer verification program  
3. Market price hint from internal data  
4. Financing calculator  
5. Second import source or dealer XML feed  

### Phase 3 — Differentiation (4+ months)
1. Embedded history reports (B2B API, dealer-paid)  
2. Compare listings  
3. Map search  
4. Content/SEO hub  

---

## VIN & vehicle history APIs

### The honest answer

**Full vehicle history (accidents, mileage, theft, import chain) is not free at scale.** Every report hits paid databases (insurance, registries, auctions). What *can* be free or cheap is different:

| Type | What you get | Cost | Good for |
|------|----------------|------|----------|
| **VIN decode (specs)** | Make, model, year, engine, body | **Free** (US gov API) or low-cost commercial | Validate listing data, catch typos |
| **Full history (US)** | Title, accidents, odometer, theft | ~**$0.25–$2+** per report at volume | US/Canada imports to BG |
| **Full history (EU)** | EU mileage, damage, theft, photos | ~**€15–30** per report retail | German/FR/IT imports — your core audience |

For Bulgaria, most valuable history is **EU import chain** (Germany, Italy, France, UK, USA). Domestic BG-only cars often have **thinner** digital history unless the car was registered/imported in EU databases.

---

### Free or very cheap options

#### 1. NHTSA VPIC (USA) — **FREE**
- **URL:** https://vpic.nhtsa.dot.gov/api/
- **Gives:** Decode VIN → make, model, year, plant, engine, body class  
- **Does NOT give:** Accidents, mileage, owners, theft  
- **Use case:** Validate that seller’s “BMW 320d 2018” matches the VIN; US import listings  
- **Limit:** US-focused; fine for 17-char VIN structure check globally  

#### 2. EU VIN decode (commercial, low tier)
- **Vincario**, **Vindecoder.eu**, **MarketCheck** — decoder tiers often **€0.01–0.10 per decode** at volume  
- Specs + sometimes country of manufacture  
- Still **not** full history  

---

### Full history — providers worth evaluating

#### carVertical (strong for EU / Bulgaria audience)
- **Site:** https://www.carvertical.com  
- **Retail:** ~**€20–30 per report** (bundle discounts)  
- **Data:** 1000+ sources, mileage, damage, theft, photos, timeline — popular in EU  
- **B2B/API:** Contact for dealer/marketplace integration; common model is **per-report wholesale** lower than retail  
- **Free trial:** Consumer site sometimes has promos; **no unlimited free API**  
- **Best MVP for AutoClasi:** Affiliate / “Check with carVertical” button → you earn per sale, **zero API risk**

#### autoDNA (EU + US, B2B-friendly)
- **Site:** https://www.autodna.com  
- **Partners:** https://www.autodna.com/company/partners-area — explicitly lists **advertising portals** and **WebAPI**  
- **Affiliate:** https://afilio.autodna.com  
- **Data:** 26+ countries  
- **Pricing:** B2B is **quote-based**; expect **single-digit to low double-digit EUR** per report wholesale depending on volume  
- **Good fit:** Marketplace partnership — embed widget or API when volume justifies it  

#### VinAudit (USA / Canada — import cars)
- **Site:** https://www.vinaudit.com/vehicle-history-api  
- **API:** Yes, with **`mode=test`** for development  
- **Pricing:** Blog cites **~$1/report** starting, **~$0.25** at high volume (NMVTIS-based)  
- **Use case:** Cars imported from USA; not primary for DE/IT market  
- **Affiliate/reseller** programs exist  

#### Carfax / AutoCheck (USA)
- **Retail expensive** (~$40+); API is enterprise  
- Skip unless you target US imports heavily  

#### Other names to compare
- **Vehicle Databases** — various APIs, quote-based  
- **MarketCheck** — inventory + history, US-heavy  
- **Experian AutoCheck** — enterprise  

---

### Recommended approach for AutoClasi (practical & affordable)

```
Step 1 (now, ~€0)
  ├── Add VIN to listing form (optional → encouraged → required for dealers)
  ├── Free NHTSA decode for US VINs + basic format validation for all
  └── Button: "Провери история" → affiliate link (carVertical or autoDNA)

Step 2 (low cost)
  ├── Pay-per-decode API to auto-fill make/model/year from VIN on publish
  └── Flag mismatches: "VIN says 2017, listing says 2019"

Step 3 (when you have traffic / dealer demand)
  ├── B2B deal with carVertical or autoDNA
  ├── Dealer buys "History package" → you pull report via API, attach PDF to listing
  └── Or buyer pays at checkout on your site (you markup €5–10)

Step 4 (optional)
  └── Bulk US import reports via VinAudit for "внос от USA" category
```

**Do not promise “free full history check”** unless you absorb €15–30 per click — it does not exist sustainably.

**Do promise:** “Verify VIN matches the car” (free) + “Order full EU report in one click” (paid/affiliate).

---

### Legal & UX notes (Bulgaria / EU)

- History reports are **informational**, not legal guarantees — state that clearly (carVertical/autoDNA do).  
- **GDPR:** If you store report data, you need retention policy and processor agreements with the API vendor.  
- **Affiliate links** must be disclosed (cookie consent you already have helps).  
- Show **“Report available”** badge only when a report is actually attached — avoids trust damage.  

---

## Features matrix (quick reference)

| Feature | AutoScout24 | mobile.de | Autotrader | CarGurus | AutoClasi today | Suggestion |
|---------|-------------|-----------|------------|----------|-----------------|------------|
| Advanced search | ✓ | ✓ | ✓ | ✓ | ✓ | Add saved search |
| Location oblast/city | ✓ | ✓ | ✓ | ✓ | ✓ | Done |
| Abroad location | ✓ | ✓ | ✓ | — | ✓ | Done |
| Favorites | ✓ | ✓ | ✓ | ✓ | ✓ | Add price drop alert |
| Dealer pages | ✓ | ✓ | ✓ | ✓ | ✓ | Add verification |
| VIN on listing | ✓ | ✓ | ✓ | ✓ | Partial | Encourage + validate |
| History report | Partner | Partner | ✓ | ✓ | — | Affiliate → API |
| Price vs market | ✓ | ✓ | ✓ | ✓✓ | — | P2 internal data |
| Finance calc | ✓ | ✓✓ | ✓ | ✓ | — | P1 easy win |
| Saved search alert | ✓ | ✓ | ✓ | ✓ | — | **P1** |
| Featured ads | ✓ | ✓ | ✓ | ✓ | — | P2 revenue |
| Import/sync | Feeds | Feeds | Feeds | — | mobile.bg | More sources |
| Compare cars | ✓ | ✓ | ✓ | ✓ | — | P2 |
| Map search | ✓ | ✓ | ✓ | ✓ | — | P2 |

---

## What Bulgarian competitors (mobile.bg, cars.bg) typically have

Useful to match or beat:

- Very deep **brand/model** taxonomy — you’re aligned  
- **Dealer subscriptions** and ad packages  
- **“Обява №”** and long-standing trust in ad numbers — you have ad numbers for companies  
- **SMS / phone-first** contact — WhatsApp button is a modern upgrade  
- They generally **lack** modern UX, transparent price history, and integrated EU history — **your opportunity**

---

## Summary

1. **Biggest wins without heavy cost:** saved search alerts, seller analytics, VIN validation + free decode, WhatsApp share, financing calculator, featured listings.  
2. **VIN full history:** start with **affiliate** (carVertical / autoDNA); move to **B2B API** when dealers or buyers pay for it. No reliable free full-history API exists.  
3. **EU focus:** carVertical and autoDNA match Bulgarian buyers checking German/Italian imports better than US-only Carfax/VinAudit.  
4. **Trust beats features:** verified dealers, honest badges, price transparency — matters more in BG than another filter checkbox.

---

## Links to explore

| Resource | URL |
|----------|-----|
| NHTSA free VIN API | https://vpic.nhtsa.dot.gov/api/ |
| carVertical | https://www.carvertical.com |
| carVertical pricing | https://www.carvertical.com/pricing |
| autoDNA partners | https://www.autodna.com/company/partners-area |
| autoDNA affiliate | https://afilio.autodna.com |
| VinAudit API docs | https://www.vinaudit.com/vehicle-history-api |
| VinAudit pricing notes | https://www.vinaudit.com/affordable-vs-premium-vehicle-history-api |
| NMVTIS (US official) | https://vehiclehistory.bja.ojp.gov/nmvtis_vehiclehistory |

---

*This is a living doc — reorder priorities as AutoClasi grows dealer base and traffic.*