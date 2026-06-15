<?php
declare(strict_types=1);

namespace ETechFlow\BannerSlider\Controller\Adminhtml\Banner;

use ETechFlow\BannerSlider\Api\BannerRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\Page;

class Edit extends Action
{
    public const ADMIN_RESOURCE = 'ETechFlow_BannerSlider::banner';

    public function __construct(
        Context $context,
        private readonly BannerRepositoryInterface $bannerRepository
    ) {
        parent::__construct($context);
    }

    public function execute(): ResultInterface
    {
        $bannerId = (int)$this->getRequest()->getParam('banner_id');

        if ($bannerId) {
            try {
                $this->bannerRepository->getById($bannerId);
            } catch (NoSuchEntityException $e) {
                $this->messageManager->addErrorMessage(__('This banner no longer exists.'));
                /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
                $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                return $resultRedirect->setPath('*/*/');
            }
        }

        /** @var Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('ETechFlow_BannerSlider::banner');
        $resultPage->getConfig()->getTitle()->prepend(
            $bannerId ? __('Edit Banner') : __('New Banner')
        );
        return $resultPage;
    }
}
