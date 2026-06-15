<?php
declare(strict_types=1);

namespace ETechFlow\BannerSlider\Controller\Adminhtml\Banner;

use ETechFlow\BannerSlider\Api\BannerRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class Delete extends Action
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
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $bannerId = (int)$this->getRequest()->getParam('banner_id');

        if ($bannerId) {
            try {
                $this->bannerRepository->deleteById($bannerId);
                $this->messageManager->addSuccessMessage(__('The banner has been deleted.'));
                return $resultRedirect->setPath('*/*/');
            } catch (NoSuchEntityException $e) {
                $this->messageManager->addErrorMessage(__('This banner no longer exists.'));
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage($e, __('Could not delete the banner.'));
                return $resultRedirect->setPath('*/*/edit', ['banner_id' => $bannerId]);
            }
        }

        $this->messageManager->addErrorMessage(__('We can\'t find a banner to delete.'));
        return $resultRedirect->setPath('*/*/');
    }
}
