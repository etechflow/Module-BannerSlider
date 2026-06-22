<?php

declare(strict_types=1);

namespace ETechFlow\BannerSlider\Model;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Component\ComponentRegistrarInterface;
use Magento\Framework\HTTP\Client\CurlFactory;

/**
 * Checks the eTechFlow private Composer repo for a newer published version of
 * this module and reports whether an update is available. Backs both the admin
 * top-bar "update available" banner and the bell-icon (AdminNotification) entry.
 *
 * Faithful port of the ETechFlow_ImageOptimizer update-notice mechanism, with
 * one addition: the installed version also falls back to this module's own
 * composer.json, so it works for file-copy installs too (where Composer's
 * InstalledVersions registry does not know the package).
 *
 * Fully fail-safe: any network/parse error simply reports "no update". The
 * latest-version lookup is cached for 6h so the admin page never blocks.
 */
class UpdateChecker
{
    public const PACKAGE = 'etechflow/module-banner-slider';

    private const LATEST_URL = 'https://license-service.etechflow.com/composer/latest/etechflow/module-banner-slider.json';
    private const CACHE_KEY   = 'etechflow_bs_latest_version';
    private const CACHE_TTL   = 21600; // 6 hours
    private const MODULE_NAME = 'ETechFlow_BannerSlider';

    public function __construct(
        private readonly CurlFactory $curlFactory,
        private readonly CacheInterface $cache,
        private readonly ComponentRegistrarInterface $componentRegistrar
    ) {
    }

    /**
     * Returns update details when a newer version is published, otherwise null.
     *
     * @return array{installed:string,latest:string,notes:string,package:string}|null
     */
    public function getAvailableUpdate(): ?array
    {
        try {
            $latest = $this->fetchLatest();
            if ($latest['version'] === '') {
                return null;
            }
            $installed = $this->installedVersion();
            if ($installed === '' || version_compare($installed, $latest['version'], '>=')) {
                return null;
            }
            return [
                'installed' => $installed,
                'latest'    => $latest['version'],
                'notes'     => $latest['notes'],
                'package'   => self::PACKAGE,
            ];
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function getUpdateCommand(): string
    {
        return 'composer update ' . self::PACKAGE;
    }

    /**
     * Latest published version + notes from the private repo (cached 6h).
     *
     * @return array{version:string,notes:string}
     */
    private function fetchLatest(): array
    {
        $raw = $this->cache->load(self::CACHE_KEY);
        if ($raw === false || $raw === null || $raw === '') {
            $raw = '{}';
            try {
                $curl = $this->curlFactory->create();
                $curl->setTimeout(5);
                $curl->get(self::LATEST_URL);
                if ((int) $curl->getStatus() === 200) {
                    $raw = (string) $curl->getBody();
                }
            } catch (\Throwable $e) {
                $raw = '{}';
            }
            $this->cache->save($raw, self::CACHE_KEY, [], self::CACHE_TTL);
        }

        $data = json_decode((string) $raw, true);
        if (!is_array($data) || empty($data['latest_version'])) {
            return ['version' => '', 'notes' => ''];
        }
        return [
            'version' => (string) $data['latest_version'],
            'notes'   => (string) ($data['release_notes'] ?? ''),
        ];
    }

    /**
     * Installed version: Composer registry first, then composer.json fallback
     * for file-copy installs.
     */
    private function installedVersion(): string
    {
        if (class_exists('\Composer\InstalledVersions')) {
            try {
                $v = \Composer\InstalledVersions::getPrettyVersion(self::PACKAGE);
                if ($v) {
                    return ltrim((string) $v, 'v');
                }
            } catch (\Throwable $e) {
                // fall through to the composer.json fallback
            }
        }

        try {
            $path = $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, self::MODULE_NAME);
            if ($path) {
                $file = $path . '/composer.json';
                if (is_file($file)) {
                    $json = json_decode((string) file_get_contents($file), true);
                    if (is_array($json) && !empty($json['version'])) {
                        return ltrim((string) $json['version'], 'v');
                    }
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }

        return '';
    }
}
