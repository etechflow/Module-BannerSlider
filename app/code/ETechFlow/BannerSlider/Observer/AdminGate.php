<?php

declare(strict_types=1);

namespace ETechFlow\BannerSlider\Observer;

use ETechFlow\BannerSlider\Model\LicenseValidator;
use Magento\Backend\Model\UrlInterface;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * License gate for the module's own admin route. Fires on every
 * etechflow_bannerslider/* controller (Sliders, Banners, Statistics). When the
 * license is not valid (suspended / expired / IP-blocked / missing) it stops
 * dispatch and redirects to the suspension card, so the module's admin screens
 * are not usable without an active licence.
 *
 * Skips the suspension page itself to avoid a redirect loop. The system-config
 * section lives under the separate "adminhtml" route, so it stays reachable for
 * pasting a new key.
 */
class AdminGate implements ObserverInterface
{
    private const SUSPENDED_ACTION = 'etechflow_bannerslider_license_suspended';

    public function __construct(
        private readonly LicenseValidator $licenseValidator,
        private readonly UrlInterface $backendUrl,
        private readonly ActionFlag $actionFlag
    ) {
    }

    public function execute(Observer $observer): void
    {
        $action = $observer->getEvent()->getControllerAction();
        if ($action === null) {
            return;
        }
        if ($action->getRequest()->getFullActionName() === self::SUSPENDED_ACTION) {
            return;
        }
        if ($this->licenseValidator->isValid()) {
            return;
        }

        $this->actionFlag->set('', ActionInterface::FLAG_NO_DISPATCH, true);
        $action->getResponse()->setRedirect(
            $this->backendUrl->getUrl('etechflow_bannerslider/license/suspended')
        );
    }
}
