<?php
declare(strict_types=1);

namespace ETechFlow\BannerSlider\Controller\Adminhtml\Report;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\PageFactory;

class Dashboard extends Action
{
    public const ADMIN_RESOURCE = 'ETechFlow_BannerSlider::report';

    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
    }

    public function execute(): ResultInterface
    {
        $result = $this->resultPageFactory->create();
        $result->setActiveMenu('ETechFlow_BannerSlider::report');
        $result->getConfig()->getTitle()->prepend(__('Banner Slider Statistics'));
        return $result;
    }
}
