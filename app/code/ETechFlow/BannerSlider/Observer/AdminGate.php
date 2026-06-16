<?php

declare(strict_types=1);

namespace ETechFlow\BannerSlider\Observer;

use ETechFlow\BannerSlider\Model\LicenseValidator;
use Magento\Backend\Model\UrlInterface;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Admin gate for the module's own backend route (etechflow_bannerslider).
 *
 * Registered ONLY on controller_action_predispatch_etechflow_bannerslider, so
 * its blast radius is exactly this module's admin controllers — Sliders,
 * Banners, Statistics and the image uploader. When the license is invalid it
 * short-circuits the dispatch and redirects to the license gate. The license
 * controllers (controller "license") are skipped to avoid an infinite redirect
 * loop.
 */
class AdminGate implements ObserverInterface
{
    public function __construct(
        private readonly LicenseValidator $licenseValidator,
        private readonly UrlInterface $backendUrl,
        private readonly ActionFlag $actionFlag
    ) {
    }

    public function execute(Observer $observer): void
    {
        $request = $observer->getEvent()->getRequest();
        if ($request === null) {
            return;
        }

        if (strtolower((string) $request->getControllerName()) === 'license') {
            return;
        }

        if ($this->licenseValidator->isValid()) {
            return;
        }

        $this->actionFlag->set('', Action::FLAG_NO_DISPATCH, true);

        $action = $observer->getEvent()->getControllerAction();
        if ($action !== null && method_exists($action, 'getResponse')) {
            $action->getResponse()->setRedirect(
                $this->backendUrl->getUrl('etechflow_bannerslider/license/gate')
            );
        }
    }
}
