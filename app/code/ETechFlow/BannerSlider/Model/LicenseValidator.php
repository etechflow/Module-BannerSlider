<?php

declare(strict_types=1);

namespace ETechFlow\BannerSlider\Model;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * License validation for ETechFlow_BannerSlider.
 *
 * Follows LICENSING_IMPLEMENTATION_GUIDE.md (reference: ETechFlow_ImageOptimizer):
 *   - SP-XXXX keys  -> live portal validation against license-service.etechflow.com
 *                      (domain + server IP must match; result cached briefly).
 *   - HMAC keys     -> local HMAC-SHA256 per-module key OR shared bundle key.
 *   - Common dev hostnames auto-detect and bypass.
 *
 * Keys are obtained by buying a licence on module.etechflow.com (the webstore)
 * and pasting the SP-XXXX key into the License config field — there is no
 * in-admin checkout. Suspension, expiry and server-IP binding are enforced and
 * reset portal-side (module.etechflow.com admin), so a revoked key disables the
 * module within the cache window without any module-side key rewriting.
 *
 * IMPORTANT (protocol): MODULE_ID + SECRET_FRAGMENTS are unique to this
 * module; BUNDLE_ID + BUNDLE_SECRET_FRAGMENTS + XML_PATH_BUNDLE_LICENSE_KEY
 * are byte-identical across EVERY eTechFlow module so a single bundle key
 * activates all of them. Do not change the bundle constants here without
 * changing them everywhere.
 */
class LicenseValidator
{
    // ── per-module config paths ─────────────────────────────────────────────
    public const XML_PATH_LICENSE_KEY            = 'etechflow_bannerslider/license/license_key';
    public const XML_PATH_ISSUED_KEY             = 'etechflow_bannerslider/license/issued_key';
    public const XML_PATH_ISSUED_AT              = 'etechflow_bannerslider/license/issued_at';
    public const XML_PATH_IP_BLOCKED             = 'etechflow_bannerslider/license/ip_blocked';
    public const XML_PATH_PORTAL_URL             = 'etechflow_bannerslider/license/portal_url';
    public const XML_PATH_PRODUCTION_ENVIRONMENT = 'etechflow_bannerslider/license/production_environment';

    /** Shared bundle config path — same value across all eTechFlow modules. */
    public const XML_PATH_BUNDLE_LICENSE_KEY = 'etechflow_bundle/license/license_key';

    // ── portal ──────────────────────────────────────────────────────────────
    private const DEFAULT_PORTAL_URL   = 'https://license-service.etechflow.com/license/validate';
    public  const PORTAL_CACHE_TTL     = 30;   // 30 s — suspensions apply within 30 s
    public  const PORTAL_CACHE_TTL_BAD = 60;   // 60 s — re-check quickly after block lifted

    // ── cache (unique per module) ────────────────────────────────────────────
    private const CACHE_TAG    = 'ETECHFLOW_BS';
    private const CACHE_PREFIX = 'etf_bs_lic_';

    // ── HMAC — per-module (UNIQUE to banner-slider; do not reuse elsewhere) ───
    private const MODULE_ID = 'banner-slider';

    private const SECRET_FRAGMENTS = [
        'REDACTED-BS-FRAGMENT-1',
        'REDACTED-BS-FRAGMENT-2',
        'REDACTED-BS-FRAGMENT-3',
        'REDACTED-BS-FRAGMENT-4',
    ];

    // ── HMAC — shared bundle (MUST be identical in every eTechFlow module) ──
    private const BUNDLE_ID = 'etechflow-bundle';

    private const BUNDLE_SECRET_FRAGMENTS = [
        'REDACTED-BUNDLE-FRAGMENT-1',
        'REDACTED-BUNDLE-FRAGMENT-2',
        'REDACTED-BUNDLE-FRAGMENT-3',
        'REDACTED-BUNDLE-FRAGMENT-4',
    ];

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly StoreManagerInterface $storeManager,
        private readonly CacheInterface $cache,
        private readonly Curl $curl,
        private readonly WriterInterface $configWriter
    ) {
    }

    // ── public API ──────────────────────────────────────────────────────────

    public function isValid(): bool
    {
        $host = $this->getCurrentHost();
        if ($host === '') {
            return false;
        }
        return $this->checkKey($host);
    }

    public function computeKey(string $host): string
    {
        $payload = $this->canonicalize($host) . ':' . self::MODULE_ID;
        $secret  = implode('', self::SECRET_FRAGMENTS);
        $raw     = hash_hmac('sha256', $payload, $secret, true);
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    public function computeBundleKey(string $host): string
    {
        $payload = $this->canonicalize($host) . ':' . self::BUNDLE_ID;
        $secret  = implode('', self::BUNDLE_SECRET_FRAGMENTS);
        $raw     = hash_hmac('sha256', $payload, $secret, true);
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    public function canonicalize(string $host): string
    {
        $host = strtolower(trim($host));
        if (str_starts_with($host, 'www.')) {
            $host = substr($host, 4);
        }
        return $host;
    }

    public function getConfiguredKey(): string
    {
        $value = $this->scopeConfig->getValue(self::XML_PATH_LICENSE_KEY, ScopeInterface::SCOPE_STORE);
        return trim((string) $value);
    }

    public function getConfiguredBundleKey(): string
    {
        $value = $this->scopeConfig->getValue(self::XML_PATH_BUNDLE_LICENSE_KEY, ScopeInterface::SCOPE_STORE);
        return trim((string) $value);
    }

    public function getPortalUrl(): string
    {
        $value = trim((string) $this->scopeConfig->getValue(self::XML_PATH_PORTAL_URL));
        return $value !== '' ? $value : self::DEFAULT_PORTAL_URL;
    }

    public function getCurrentHost(): string
    {
        try {
            $url  = $this->storeManager->getStore()->getBaseUrl();
            $host = parse_url($url, PHP_URL_HOST);
            return is_string($host) ? strtolower($host) : '';
        } catch (\Exception) {
            return '';
        }
    }

    public function isDevHost(?string $host = null): bool
    {
        $check = $host !== null
            ? $this->canonicalize($host)
            : $this->canonicalize($this->getCurrentHost());
        return $this->isDevelopmentHost($check);
    }

    // ── private helpers ─────────────────────────────────────────────────────

    private function checkKey(string $host): bool
    {
        $configuredKey = $this->getConfiguredKey();
        if ($configuredKey === '') {
            return false;
        }

        // SP-XXXX subscription key → ALWAYS validate live against the portal
        // (result cached for PORTAL_CACHE_TTL only). No offline grace and no
        // issued-key fallback: the portal is the single source of truth, so a
        // server-IP mismatch, suspension, or expiry locks the module within the
        // cache window. This enforces the domain + server-IP binding.
        if (str_starts_with($configuredKey, 'SP-')) {
            return $this->portalResult($host, $configuredKey)['valid'];
        }

        // HMAC per-module key (offline; LICENSING_PROTOCOL.md)
        if (hash_equals($this->computeKey($host), $configuredKey)) {
            return true;
        }
        // Shared bundle key
        $bundleKey = $this->getConfiguredBundleKey();
        return $bundleKey !== '' && hash_equals($this->computeBundleKey($host), $bundleKey);
    }

    /**
     * Live portal check. Returns ['valid' => bool, 'features' => array].
     * Cached as JSON for PORTAL_CACHE_TTL so they stay atomic and the portal
     * isn't hit on every request. On portal-unreachable we fail closed without
     * caching, so the next request retries.
     *
     * @return array{valid: bool, features: array<string,mixed>}
     */
    private function portalResult(string $host, string $key): array
    {
        $cacheKey = self::CACHE_PREFIX . md5($host . ':' . $key);
        $cached   = $this->cache->load($cacheKey);
        if ($cached !== false) {
            $d = json_decode((string) $cached, true);
            if (is_array($d) && array_key_exists('valid', $d)) {
                return [
                    'valid'    => (bool) $d['valid'],
                    'features' => isset($d['features']) && is_array($d['features']) ? $d['features'] : [],
                ];
            }
        }

        $url = $this->getPortalUrl()
            . '?domain=' . urlencode($host)
            . '&license_key=' . urlencode($key)
            . '&platform=magento&module=' . self::MODULE_ID;

        $valid    = false;
        $features = [];
        $status   = 0;
        $body     = '';

        try {
            $this->curl->setTimeout(10);
            $this->curl->addHeader('Accept', 'application/json');
            $this->curl->addHeader('User-Agent', 'ETechFlow-BS/1.0');
            $this->curl->get($url);
            $status = (int) $this->curl->getStatus();
            $body   = (string) $this->curl->getBody();
        } catch (\Throwable) {
            // Portal unreachable — fail closed for THIS request without caching.
            return ['valid' => false, 'features' => []];
        }

        if ($status === 200 && $body !== '') {
            $data     = json_decode($body, true);
            $valid    = !empty($data['valid']);
            $features = (is_array($data) && isset($data['features']) && is_array($data['features'])) ? $data['features'] : [];
        }

        $ttl = $valid ? self::PORTAL_CACHE_TTL : self::PORTAL_CACHE_TTL_BAD;
        $this->cache->save(
            json_encode(['valid' => $valid, 'features' => $valid ? $features : []]),
            $cacheKey,
            [self::CACHE_TAG],
            $ttl
        );

        return ['valid' => $valid, 'features' => $valid ? $features : []];
    }

    private function isDevelopmentHost(string $host): bool
    {
        if ($host === 'localhost' || str_starts_with($host, '127.')) {
            return true;
        }
        if (str_starts_with($host, '10.') || str_starts_with($host, '192.168.')) {
            return true;
        }
        if (preg_match('/^172\.(1[6-9]|2[0-9]|3[01])\./', $host)) {
            return true;
        }
        foreach (['.test', '.local', '.localhost', '.dev', '.example', '.invalid'] as $s) {
            if (str_ends_with($host, $s)) {
                return true;
            }
        }
        foreach (['staging.', 'stage.', 'dev.', 'qa.', 'uat.', 'test.', 'preview.', 'sandbox.'] as $p) {
            if (str_starts_with($host, $p)) {
                return true;
            }
        }
        // Hyphen-dev pattern intentionally omitted: production domains may contain '-dev'
        // (e.g. magento-dev.etechflow.com is a LIVE store, not a bypass host).
        foreach (['.magento.cloud', '.magentocloud.com', '.cloud.magento'] as $s) {
            if (str_ends_with($host, $s)) {
                return true;
            }
        }
        foreach (['.ngrok.io', '.ngrok-free.app', '.loca.lt', '.serveo.net', '.ngrok-free.dev'] as $s) {
            if (str_ends_with($host, $s)) {
                return true;
            }
        }
        return false;
    }
}
