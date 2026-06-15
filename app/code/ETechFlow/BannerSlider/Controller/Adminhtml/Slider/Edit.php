<?php
declare(strict_types=1);

namespace ETechFlow\BannerSlider\Controller\Adminhtml\Slider;

use ETechFlow\BannerSlider\Api\SliderRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\Page;

class Edit extends Action
{
    public const ADMIN_RESOURCE = 'ETechFlow_BannerSlider::slider';

    public function __construct(
        Context $context,
        private readonly SliderRepositoryInterface $sliderRepository
    ) {
        parent::__construct($context);
    }

    public function execute(): ResultInterface
    {
        $sliderId = (int)$this->getRequest()->getParam('slider_id');

        if ($sliderId) {
            try {
                $this->sliderRepository->getById($sliderId);
            } catch (NoSuchEntityException $e) {
                $this->messageManager->addErrorMessage(__('This slider no longer exists.'));
                /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
                $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                return $resultRedirect->setPath('*/*/');
            }
        }

        /** @var Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('ETechFlow_BannerSlider::slider');
        $resultPage->getConfig()->getTitle()->prepend(
            $sliderId ? __('Edit Slider') : __('New Slider')
        );
        return $resultPage;
    }
}
