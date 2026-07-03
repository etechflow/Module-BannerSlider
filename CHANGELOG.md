# Changelog

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
