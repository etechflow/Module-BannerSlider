<?php
declare(strict_types=1);

namespace ETechFlow\BannerSlider\Controller\Adminhtml\Slider;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\Page;

class NewAction extends Action
{
    public const ADMIN_RESOURCE = 'ETechFlow_BannerSlider::slider';

    public function __construct(Context $context)
    {
        parent::__construct($context);
    }

    public function execute(): ResultInterface
    {
        /** @var Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('ETechFlow_BannerSlider::slider');
        $resultPage->getConfig()->getTitle()->prepend(__('New Slider'));
        return $resultPage;
    }
}
