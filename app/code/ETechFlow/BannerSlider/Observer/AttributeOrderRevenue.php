<?php
declare(strict_types=1);

namespace ETechFlow\BannerSlider\Observer;

use ETechFlow\BannerSlider\Model\Config;
use ETechFlow\BannerSlider\Model\Stat\StatRecorder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Last-click revenue attribution.
 *
 * When an order is placed on the storefront, credit its revenue back to the
 * banner whose click set the etf_bs_attr cookie (written client-side by the
 * tracker). Scoped to the frontend area via etc/frontend/events.xml so the
 * cookie is actually available; admin/API order creation is unaffected.
 */
class AttributeOrderRevenue implements ObserverInterface
{
    private const ATTR_COOKIE = 'etf_bs_attr';
    private const XML_TRACKING = 'etechflow_bannerslider/performance/async_tracking';

    public function __construct(
        private readonly CookieManagerInterface $cookieManager,
        private readonly CookieMetadataFactory $cookieMetadataFactory,
        private readonly StatRecorder $statRecorder,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly Config $config,
        private readonly Json $json
    ) {
    }

    public function execute(Observer $observer): void
    {
        if (!$this->isTrackingEnabled()) {
            return;
        }

        $cookie = $this->cookieManager->getCookie(self::ATTR_COOKIE);
        if (!$cookie) {
            return;
        }

        $order = $observer->getEvent()->getData('order');
        if (!$order || !$order->getId()) {
            return;
        }

        $attr = $this->parseCookie($cookie);
        if ($attr === null) {
            return;
        }

        $this->statRecorder->record(
            [[
                'banner_id' => $attr['b'],
                'slider_id' => $attr['s'],
                'variant' => $attr['v'],
                'event_type' => 'order',
                'cnt' => 1,
                'revenue' => (float)$order->getGrandTotal(),
            ]],
            (int)$order->getStoreId(),
            StatRecorder::SERVER_EVENTS
        );

        $this->forgetCookie();
    }

    /**
     * @param string $cookie
     * @return array{b:int,s:int,v:string}|null
     */
    private function parseCookie(string $cookie): ?array
    {
        try {
            $data = $this->json->unserialize($cookie);
        } catch (\InvalidArgumentException $e) {
            return null;
        }
        $bannerId = (int)($data['b'] ?? 0);
        $sliderId = (int)($data['s'] ?? 0);
        if ($bannerId <= 0 || $sliderId <= 0) {
            return null;
        }
        return ['b' => $bannerId, 's' => $sliderId, 'v' => (string)($data['v'] ?? 'default')];
    }

    private function forgetCookie(): void
    {
        try {
            $metadata = $this->cookieMetadataFactory->createPublicCookieMetadata()->setPath('/');
            $this->cookieManager->deleteCookie(self::ATTR_COOKIE, $metadata);
        } catch (\Exception $e) {
            // Headers already sent — the cookie will lapse at its own expiry.
        }
    }

    private function isTrackingEnabled(): bool
    {
        return $this->config->isEnabled()
            && $this->scopeConfig->isSetFlag(self::XML_TRACKING, ScopeInterface::SCOPE_STORE);
    }
}
