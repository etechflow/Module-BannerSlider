<?php
declare(strict_types=1);

namespace ETechFlow\BannerSlider\Controller\Adminhtml\Slider;

use ETechFlow\BannerSlider\Api\SliderRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class Delete extends Action
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
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $sliderId = (int)$this->getRequest()->getParam('slider_id');

        if ($sliderId) {
            try {
                $this->sliderRepository->deleteById($sliderId);
                $this->messageManager->addSuccessMessage(__('The slider has been deleted.'));
                return $resultRedirect->setPath('*/*/');
            } catch (NoSuchEntityException $e) {
                $this->messageManager->addErrorMessage(__('This slider no longer exists.'));
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage($e, __('Could not delete the slider.'));
                return $resultRedirect->setPath('*/*/edit', ['slider_id' => $sliderId]);
            }
        }

        $this->messageManager->addErrorMessage(__('We can\'t find a slider to delete.'));
        return $resultRedirect->setPath('*/*/');
    }
}
