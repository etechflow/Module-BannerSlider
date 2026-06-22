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
 * License-suspended landing page — shows the suspension card. If the licence has
 * become valid again, sends the admin straight back to the Sliders grid.
 */
class Suspended extends Action
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
            return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)
                ->setPath('etechflow_bannerslider/slider/index');
        }

        $page = $this->pageFactory->create();
        $page->getConfig()->getTitle()->prepend(__('Banner Slider — License Suspended'));
        return $page;
    }
}
