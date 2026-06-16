<?php

declare(strict_types=1);

namespace ETechFlow\BannerSlider\Controller\Adminhtml\License;

use ETechFlow\BannerSlider\Model\LicenseValidator;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Cache\Type\Config as ConfigCacheType;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\HTTP\Client\CurlFactory;
use Magento\Framework\View\Result\PageFactory;

/**
 * Landing page after Stripe payment. Calls the eTechFlow portal to activate the
 * subscription (the portal verifies the Stripe session with ITS OWN key), gets
 * the license key, saves it to config, and shows success.
 */
class Activated extends Action
{
    public const ADMIN_RESOURCE = 'ETechFlow_BannerSlider::config';

    private const RESULT_URL = 'https://module.etechflow.com/api/license/result';
    private const PORTAL_TOKEN = 'lcsk_REDACTED_PORTAL_TOKEN';

    /** Poll the portal a few times while the webhook mints the key. */
    private const MAX_ATTEMPTS = 4;
    private const RETRY_DELAY = 2;

    public function __construct(
        Context $context,
        private readonly PageFactory $pageFactory,
        private readonly CurlFactory $curlFactory,
        private readonly WriterInterface $configWriter,
        private readonly CacheInterface $cache,
        private readonly LicenseValidator $licenseValidator
    ) {
        parent::__construct($context);
    }

    public function execute(): ResultInterface
    {
        $sessionId = trim((string) $this->getRequest()->getParam('session_id', ''));
        $subId     = trim((string) $this->getRequest()->getParam('sub_id', ''));
        $plan      = trim((string) $this->getRequest()->getParam('plan', ''));
        $domain    = trim((string) $this->getRequest()->getParam('domain', '')) ?: $this->licenseValidator->getCurrentHost();
        $name      = trim((string) $this->getRequest()->getParam('name', ''));
        $email     = trim((string) $this->getRequest()->getParam('email', ''));

        if (!$sessionId) {
            $this->messageManager->addErrorMessage(__('Invalid payment callback.'));
            return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('etechflow_bannerslider/license/gate');
        }

        $payload = json_encode(array_filter([
            'session_id' => $sessionId,
            'sub_id'     => $subId ?: null,
            'domain'     => $domain,
            'name'       => $name,
            'email'      => $email,
            'plan'       => $plan,
        ]));

        $licenseKey = '';
        $planName   = $plan;
        $error      = '';
        $pending    = false;

        // The key is minted by the Stripe webhook, which can land a few seconds
        // after this redirect — so poll a few times before giving up.
        for ($attempt = 0; $attempt < self::MAX_ATTEMPTS; $attempt++) {
            if ($attempt > 0) {
                sleep(self::RETRY_DELAY);
            }
            [$status, $data, $body] = $this->callResult($payload);

            if ($status === 200 && !empty($data['license_key'])) {
                $licenseKey = (string) $data['license_key'];
                $planName   = (string) ($data['plan'] ?? $plan);
                $pending    = false;
                $error      = '';
                break;
            }
            if ($status === 200 && (($data['status'] ?? '') === 'pending')) {
                // Payment captured, key not issued yet — keep waiting.
                $pending  = true;
                $planName = (string) ($data['plan'] ?? $plan);
                continue;
            }
            // Anything else is a real failure.
            $error   = is_array($data) && !empty($data['error'])
                ? (string) $data['error']
                : ('Portal returned status ' . $status . ': ' . $body);
            $pending = false;
            break;
        }

        if ($licenseKey) {
            $this->configWriter->save(LicenseValidator::XML_PATH_LICENSE_KEY, $licenseKey);
            $this->cache->clean([ConfigCacheType::CACHE_TAG]);
        }

        $page = $this->pageFactory->create();
        $page->getConfig()->getTitle()->prepend(__('Subscription Activated'));

        $block = $page->getLayout()->getBlock('etechflow.bannerslider.license.activated');
        if ($block) {
            $block->setData('license_key', $licenseKey)
                  ->setData('plan', $planName)
                  ->setData('error', $error)
                  ->setData('pending', $pending && $licenseKey === '')
                  ->setData('settings_url', $this->getUrl('adminhtml/system_config/edit/section/etechflow_bannerslider'))
                  ->setData('management_url', $this->getUrl('etechflow_bannerslider/license/gate'));
        }

        return $page;
    }

    /**
     * Single call to the portal result endpoint.
     *
     * @param string $payload
     * @return array{0:int,1:array|null,2:string} [status, decoded, raw body]
     */
    private function callResult(string $payload): array
    {
        try {
            $curl = $this->curlFactory->create();
            $curl->setTimeout(15);
            $curl->addHeader('Content-Type', 'application/json');
            $curl->addHeader('Accept', 'application/json');
            $curl->addHeader('X-ETF-License-Token', self::PORTAL_TOKEN);
            $curl->post(self::RESULT_URL, $payload);
            return [(int) $curl->getStatus(), json_decode((string) $curl->getBody(), true), (string) $curl->getBody()];
        } catch (\Throwable $e) {
            return [0, null, 'Could not reach portal: ' . $e->getMessage()];
        }
    }
}
