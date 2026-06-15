<?php
declare(strict_types=1);

namespace ETechFlow\BannerSlider\Controller\Adminhtml\Slider;

use ETechFlow\BannerSlider\Api\SliderRepositoryInterface;
use ETechFlow\BannerSlider\Model\ResourceModel\Slider\CollectionFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Ui\Component\MassAction\Filter;

class MassDelete extends Action
{
    public const ADMIN_RESOURCE = 'ETechFlow_BannerSlider::slider';

    public function __construct(
        Context $context,
        private readonly Filter $filter,
        private readonly CollectionFactory $collectionFactory,
        private readonly SliderRepositoryInterface $sliderRepository
    ) {
        parent::__construct($context);
    }

    public function execute(): ResultInterface
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        try {
            $collection = $this->filter->getCollection($this->collectionFactory->create());
            $deleted = 0;
            foreach ($collection as $slider) {
                $this->sliderRepository->delete($slider);
                $deleted++;
            }
            $this->messageManager->addSuccessMessage(
                __('A total of %1 record(s) have been deleted.', $deleted)
            );
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('Could not delete the selected sliders.'));
        }

        return $resultRedirect->setPath('*/*/');
    }
}
