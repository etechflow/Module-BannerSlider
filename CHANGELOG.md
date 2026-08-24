# Changelog

## Diagnostics: explain why an empty slider rendered nothing

A storefront slider with nothing to render no longer produces a silent blank —
it emits a short HTML comment stating the reason, visible in the page source
(View Source / Ctrl+U). It distinguishes: module disabled/licence invalid; the
widget's `slider_id` not found or disabled (most often a slider that was deleted
and recreated under a new id — the widget still points at the old one); the
slider has no active banners for the store view; or all banners were skipped.
No customer-visible output changes.

## 1.0.10 — Visibility: de-duplicate "All Store Views"

The Store Views multiselect listed "All Store Views" twice: the source prepends
its own entry AND called `System\Store::getStoreValuesForForm(false, true)`,
whose `true` prepends another. Switched that call to `false` so only our single
"All Store Views" (value `'0'`) is shown. Completes the 1.0.9 visibility fix.

## 1.0.9 — Marketplace QA fixes: banner/slider visibility + keyless storefront

Fixes the two issues raised in Adobe Commerce Marketplace manual QA.

- **Visibility (Store Views / Customer Groups) not saving.** The save path was
  correct; the multiselects failed to *reload* their selection because the model
  getters return `int` ids while the option sources expose `string` values, so a
  multiselect never re-highlighted the saved value (looked like it wasn't saved).
  The `Banner` and `Slider` DataProviders now cast the ids to strings at the form
  boundary (`array_map('strval', …)`), and the `StoreView` source emits `'0'`
  for "All Store Views" so it matches too. Model getters stay int-typed, so the
  storefront store/group filters are unchanged.
- **Storefront slider rendered nothing on a keyless install.** `config.xml`
  defaulted `production_environment` to `1`, so `LicenseValidator::isValid()`
  demanded a portal `SP-` key and returned false on any install without one —
  including the Marketplace QA environment — blanking the whole storefront.
  The default is now `0`: a plain install works out of the box; merchants who
  license through the eTechFlow portal switch it to Yes and add their SP- key.
  (This intentionally restores the config-settable non-production mode for
  Marketplace compatibility.)

## Security: portal-only licensing (removes forgeable key path)

The `LicenseValidator` previously shipped its HMAC signing secret
(`SECRET_FRAGMENTS` / `BUNDLE_SECRET_FRAGMENTS`) inside the PHP and validated a
locally-computed key against admin config. Anyone with the module could forge a
valid key for their own domain via admin — no code edit required.

- Removed the shipped secret and all local key computation (`computeKey`,
  `computeBundleKey`, `checkKey`, `SECRET_FRAGMENTS`, `BUNDLE_SECRET_FRAGMENTS`).
- Validation is now portal-only: only portal-issued `SP-` keys are honoured, and
  the portal's answer (valid / reject / 401 / 403) is authoritative.
- Offline grace derives solely from a cached genuine portal success, keyed to
  host+key — it can no longer be fabricated from admin-settable config.
- `production_environment` is hardcoded on; the client-settable dev bypass is gone.
- Rewrote the unit suite as a portal-only suite, incl. a hard test that a forged
  `SP-` key plus attacker-controlled config plus no portal is rejected.
