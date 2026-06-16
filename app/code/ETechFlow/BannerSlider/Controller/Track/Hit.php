<?php
declare(strict_types=1);

namespace ETechFlow\BannerSlider\Controller\Track;

use ETechFlow\BannerSlider\Model\Config;
use ETechFlow\BannerSlider\Model\Stat\StatRecorder;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Storefront analytics beacon endpoint (POST /bannerslider/track/hit).
 *
 * Receives a small JSON batch of impression/click/add_to_cart events from the
 * slider JS (navigator.sendBeacon) and folds them into the aggregated stat
 * table. CSRF is intentionally skipped: the payload is anonymous, write-only
 * analytics with no state-changing effect, and a form key cannot accompany a
 * beacon. Revenue/order events are never accepted here — those are attributed
 * server-side on order placement.
 */
class Hit implements HttpPostActionInterface, CsrfAwareActionInterface
{
    private const MAX_EVENTS = 50;
    private const XML_TRACKING = 'etechflow_bannerslider/performance/async_tracking';

    public function __construct(
        private readonly HttpRequest $request,
        private readonly ResultFactory $resultFactory,
        private readonly StatRecorder $statRecorder,
        private readonly StoreManagerInterface $storeManager,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly Config $config,
        private readonly Json $json
    ) {
    }

    public function execute(): ResultInterface
    {
        /** @var Raw $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_RAW);
        $result->setHttpResponseCode(204);

        if (!$this->isTrackingEnabled()) {
            return $result;
        }

        $events = $this->parseEvents($this->request->getContent());
        if (!$events) {
            return $result;
        }

        try {
            $storeId = (int)$this->storeManager->getStore()->getId();
            $this->statRecorder->record($events, $storeId, StatRecorder::CLIENT_EVENTS);
        } catch (\Throwable $e) {
            // Analytics must never surface an error to the storefront.
            return $result;
        }

        return $result;
    }

    /**
     * @param string $content
     * @return array
     */
    private function parseEvents(string $content): array
    {
        if ($content === '') {
            return [];
        }
        try {
            $payload = $this->json->unserialize($content);
        } catch (\InvalidArgumentException $e) {
            return [];
        }
        $events = $payload['events'] ?? null;
        if (!is_array($events)) {
            return [];
        }
        return array_slice($events, 0, self::MAX_EVENTS);
    }

    private function isTrackingEnabled(): bool
    {
        // config->isEnabled() includes the license gate.
        return $this->config->isEnabled()
            && $this->scopeConfig->isSetFlag(self::XML_TRACKING, ScopeInterface::SCOPE_STORE);
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
