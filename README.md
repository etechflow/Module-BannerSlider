# ETechFlow Banner Slider for Magento 2 / Adobe Commerce

An advanced banner slider that matches the Amasty Banner Slider feature set and adds
**four differentiating features**. Compatible with **Luma**, **Hyvä** (incl. **Hyvä Checkout**),
and **Adobe Commerce**, and engineered to stay **production-fast** (Full Page Cache / Varnish-safe).

## Modules

| Module | Purpose | Install when |
|--------|---------|--------------|
| `ETechFlow_BannerSlider` | Core: admin, DB, logic, Luma frontend, REST/Repository API | Always |
| `ETechFlow_BannerSliderHyva` | Hyvä storefront rendering (Tailwind + Alpine.js, no jQuery/RequireJS) | Store uses Hyvä |
| `ETechFlow_BannerSliderGraphQl` | GraphQL for headless / PWA / Hyvä Checkout | Headless / PWA stores |

## Baseline features (parity with Amasty)
Sliders & banners, desktop/mobile images, target URL + alt text, scheduling (start/end),
customer-group & store-view targeting, drag-n-drop ordering, enable/disable, autoplay & speed,
transition effects, arrows + bullets, loop, widget placement (Content > Widgets + layout updates),
responsive auto-resize.

## The 4 differentiators

### 1. Smart rule-based targeting (FPC-safe)
Show/hide each banner by **login state, customer group, device, country, cart quantity,
cart subtotal, day-of-week, hour-of-day and UTM** (all combined with AND; an unset rule is
ignored). Rules are stored as JSON and **evaluated in the browser** against a per-visitor
context delivered through a customer-data section — so the cached slider markup is identical
for every visitor and Full Page Cache / Varnish stay intact. (Country targeting needs a
GeoIP / CDN country header such as Cloudflare's `CF-IPCountry`.)

### 2. Banner types beyond static images
**Image**, **Video** (self-hosted MP4 or YouTube/Vimeo — heavy players load lazily on play or
when the slide becomes active), **HTML / rich content**, **Product** (image, price and
FPC-safe add-to-cart), and **Countdown timer** (timezone-correct; can hide on expiry).

### 3. A/B testing
Group banners into variants with traffic **weights**; visitors are split client-side and the
choice is **sticky per visitor** (cookie). An optional daily cron auto-concludes a test once
each variant has enough impressions, picking the winner by **CTR / add-to-cart / revenue**;
after conclusion only the winning variant is served (filtered server-side).

### 4. Analytics + conversion attribution
Viewport-accurate **impressions**, **clicks** (CTR), **add-to-cart**, and **order revenue**
attributed per banner (last-click). Events are sent with `navigator.sendBeacon` and folded into
a daily-aggregated table; order revenue is attributed server-side on order placement. View it
under **Content → Banner Slider → Statistics** (KPI cards, per-banner table, per-variant A/B
breakdown) with **CSV export**.

## Performance posture
Lazy-loaded images and video facades, `<picture>` with mobile sources, no CLS (reserved
aspect-ratio), `sendBeacon` async tracking, and all personalization (targeting, A/B) resolved
client-side via customer-data so the rendered markup is fully cacheable.

## Requirements
- Magento Open Source / Adobe Commerce **2.4.6 – 2.4.9** (tested on 2.4.9)
- PHP **8.1 / 8.2 / 8.3 / 8.4**
- Hyvä Themes **≥ 1.3** (for the Hyvä module only)

## Install
Copy the module folders into `app/code/ETechFlow/`, then:
```bash
bin/magento module:enable ETechFlow_BannerSlider ETechFlow_BannerSliderGraphQl
# On a Hyvä storefront also:
bin/magento module:enable ETechFlow_BannerSliderHyva
bin/magento setup:upgrade
bin/magento setup:di:compile          # production mode only
bin/magento cache:flush
```

## Admin usage
- **Content → Banner Slider → Sliders / Banners** — create sliders, create banners (pick a type;
  the form reveals type-specific fields), then assign banners to a slider with a position,
  A/B variant label and weight.
- Place a slider on any page via **Content → Widgets** ("ETechFlow Banner Slider", or
  "ETechFlow Banner Slider (Hyvä)" on Hyvä) or the "Insert Widget" button in the CMS editor.
- **Content → Banner Slider → Statistics** — analytics dashboard + CSV export.

## Configuration
**Stores → Configuration → ETechFlow → Banner Slider** (paths under `etechflow_bannerslider/`):

| Path | Default | Purpose |
|------|---------|---------|
| `general/enabled` | 1 | Master on/off |
| `performance/lazy_load` | 1 | Defer off-screen slide images |
| `performance/webp` | 1 | Prefer WebP sources |
| `performance/async_tracking` | 1 | Enable `sendBeacon` analytics |
| `targeting/geo_enabled` | 0 | Country targeting (needs a GeoIP/CDN header) |
| `analytics/attribution_window` | 7 | Days a click is credited with a later order |
| `analytics/ab_auto_conclude` | 0 | Let the cron auto-pick A/B winners |
| `analytics/ab_min_impressions` | 1000 | Impressions/variant required before auto-conclude |

## Cron
`etechflow_bannerslider_conclude_ab` (daily, 03:00) — auto-concludes eligible A/B tests when
`analytics/ab_auto_conclude` is enabled.

## GraphQL API (headless / PWA / Hyvä Checkout)
```graphql
query {
  etechflowBannerSlider(slider_id: 1) {
    slider_id title autoplay is_ab_test ab_goal ab_winner track attribution_days
    banners {
      banner_id type name image url variant weight targeting
      video_type video_url countdown_at_ms product_sku
    }
  }
}

mutation {
  etechflowTrackBannerEvent(input: { banner_id: 1, slider_id: 1, variant: "A", event_type: "impression" }) {
    success
  }
}
```
The query returns every active banner plus its targeting rules / variant / weight, so the client
performs targeting and A/B selection itself (keeping the response cacheable). `event_type` accepts
`impression | click | add_to_cart`; order revenue is attributed server-side.

## REST API (admin token)
```
GET    /V1/etechflow-bannerslider/sliders/:sliderId
POST   /V1/etechflow-bannerslider/sliders
DELETE /V1/etechflow-bannerslider/sliders/:sliderId
GET    /V1/etechflow-bannerslider/banners/:bannerId
POST   /V1/etechflow-bannerslider/banners
DELETE /V1/etechflow-bannerslider/banners/:bannerId
```

## Data model
`etechflow_bannerslider_slider`, `_banner`, `_banner_slider` (link table with A/B variant +
weight), `_stat` (daily-aggregated events + revenue).

## Status
Feature-complete: all four differentiators implemented across Luma, Hyvä and GraphQL, with
admin CRUD, analytics dashboard, REST + GraphQL APIs and unit tests.
