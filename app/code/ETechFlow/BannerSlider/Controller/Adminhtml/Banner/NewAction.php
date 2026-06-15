<?php
declare(strict_types=1);

namespace ETechFlow\BannerSlider\Controller\Adminhtml\Banner;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\Page;

class NewAction extends Action
{
    public const ADMIN_RESOURCE = 'ETechFlow_BannerSlider::banner';

    public function __construct(Context $context)
    {
        parent::__construct($context);
    }

    public function execute(): ResultInterface
    {
        /** @var Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('ETechFlow_BannerSlider::banner');
        $resultPage->getConfig()->getTitle()->prepend(__('New Banner'));
        return $resultPage;
    }
}
