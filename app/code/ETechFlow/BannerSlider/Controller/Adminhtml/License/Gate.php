<?php

declare(strict_types=1);

namespace ETechFlow\BannerSlider\Controller\Adminhtml\License;

use ETechFlow\BannerSlider\Model\LicenseValidator;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\PageFactory;

/**
 * License-required gate page. Shows plan cards + "Enter License Key".
 * Redirects to the Sliders grid when the license is already valid.
 */
class Gate extends Action
{
    public const ADMIN_RESOURCE = 'ETechFlow_BannerSlider::config';

    public function __construct(
        Context $context,
        private readonly PageFactory $pageFactory,
        private readonly LicenseValidator $licenseValidator
    ) {
        parent::__construct($context);
    }

    public function execute(): ResultInterface
    {
        if ($this->licenseValidator->isValid()) {
            $redirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            return $redirect->setPath('etechflow_bannerslider/slider/index');
        }

        $page = $this->pageFactory->create();
        $page->getConfig()->getTitle()->prepend(__('Banner Slider — License Required'));
        $portalBase = rtrim(str_replace('/license/validate', '', $this->licenseValidator->getPortalUrl()), '/');
        $domain     = $this->licenseValidator->getCurrentHost();
        $plansUrl   = $portalBase . '/license/plans?module=banner-slider&domain=' . urlencode($domain);
        $block = $page->getLayout()->getBlock('etechflow.bannerslider.license.gate');
        if ($block) {
            $block->setData('plans_url', $plansUrl);
        }
        return $page;
    }
}
