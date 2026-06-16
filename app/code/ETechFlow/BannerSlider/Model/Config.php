<?php

declare(strict_types=1);

namespace ETechFlow\BannerSlider\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Central license-aware enablement gate for ETechFlow_BannerSlider.
 *
 * isEnabled() returns false when EITHER the admin "Enable Banner Slider" toggle
 * is No OR the license is not valid. Every storefront / headless entry point
 * (block, tracking controller, GraphQL resolvers, order observer) routes its
 * enablement check through here, so the whole module is gated in one place.
 */
class Config
{
    public const XML_PATH_ENABLED = 'etechflow_bannerslider/general/enabled';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly LicenseValidator $licenseValidator
    ) {
    }

    /**
     * Module is usable only with a valid license AND the admin toggle on.
     */
    public function isEnabled(): bool
    {
        if (!$this->licenseValidator->isValid()) {
            return false;
        }
        return $this->isAdminEnabled();
    }

    /**
     * The admin "Enable Banner Slider" toggle alone (no license check).
     */
    public function isAdminEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ENABLED, ScopeInterface::SCOPE_STORE);
    }
}
