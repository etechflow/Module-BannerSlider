# ETechFlow Banner Slider for Magento 2 / Adobe Commerce

An advanced banner slider that matches the Amasty Banner Slider feature set and adds
**four differentiating features**. Compatible with **Luma**, **Hyvä** (incl. **Hyvä Checkout**),
and **Adobe Commerce**, and engineered to stay **production-fast** (FPC/Varnish-safe).

## Modules

| Module | Purpose | Install when |
|--------|---------|--------------|
| `ETechFlow_BannerSlider` | Core: admin, DB, logic, Luma frontend, REST/Repository API | Always |
| `ETechFlow_BannerSliderHyva` | Hyvä storefront rendering (Tailwind + Alpine.js) | Store uses Hyvä |
| `ETechFlow_BannerSliderGraphQl` | GraphQL for headless / PWA / Hyvä Checkout | Headless / PWA stores |

## Baseline features (parity with Amasty)
Sliders & banners, desktop/mobile images, target URL + alt text, scheduling (start/end),
customer-group & store-view targeting, drag-n-drop ordering, enable/disable, autoplay & speed,
transition effects, arrows + bullets, loop, widget placement (Content > Widgets + layout updates),
responsive auto-resize.

## The 4 differentiators
1. **Smart / rule-based targeting** — native Rule/Condition engine: cart value & contents,
   customer attributes (new vs returning, group, LTV), geo/country, device, time/day, UTM,
   logged-in vs guest. Evaluated FPC-safely.
2. **Banner types beyond static images** — video (MP4/YouTube/Vimeo), HTML/rich content,
   product banners (price + add-to-cart), countdown-timer banners.
3. **A/B testing built in** — multiple creatives per slot, weighted traffic split,
   automatic winner selection by CTR / add-to-cart / revenue.
4. **Real analytics + conversion attribution** — viewport-accurate impressions, clicks, CTR,
   plus add-to-cart and order/revenue attribution per banner, with an admin dashboard + CSV export.

## Performance posture
Lazy-loading, WebP + srcset `<picture>`, no CLS (reserved aspect-ratio), beacon-based async
tracking, and personalization via customer-data sections / ESI so Full Page Cache & Varnish stay intact.

## Requirements
- Magento Open Source / Adobe Commerce **2.4.6 – 2.4.8**
- PHP **8.1 / 8.2 / 8.3**
- Hyvä Themes **≥ 1.3** (for the Hyvä module)

## Install (local / file copy)
Copy the three module folders into `app/code/ETechFlow/`, then:
```bash
bin/magento module:enable ETechFlow_BannerSlider ETechFlow_BannerSliderHyva ETechFlow_BannerSliderGraphQl
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

## Status
Scaffold stage — module skeletons, data model (db_schema), config, ACL, menu, routing,
widget and GraphQL schema are in place. Feature implementation follows.
