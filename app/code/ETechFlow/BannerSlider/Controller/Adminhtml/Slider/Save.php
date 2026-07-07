<?php
declare(strict_types=1);

namespace ETechFlow\BannerSlider\Controller\Adminhtml\Slider;

use ETechFlow\BannerSlider\Api\Data\SliderInterface;
use ETechFlow\BannerSlider\Api\SliderRepositoryInterface;
use ETechFlow\BannerSlider\Model\ResourceModel\BannerSlider as BannerSliderResource;
use ETechFlow\BannerSlider\Model\SliderFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class Save extends Action
{
    public const ADMIN_RESOURCE = 'ETechFlow_BannerSlider::slider';

    public function __construct(
        Context $context,
        private readonly SliderRepositoryInterface $sliderRepository,
        private readonly SliderFactory $sliderFactory,
        private readonly DataPersistorInterface $dataPersistor,
        private readonly BannerSliderResource $bannerSliderResource
    ) {
        parent::__construct($context);
    }

    public function execute(): ResultInterface
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $data = $this->getRequest()->getPostValue();

        if (!$data) {
            return $resultRedirect->setPath('*/*/');
        }

        $sliderId = isset($data['slider_id']) ? (int)$data['slider_id'] : 0;

        // Pull assigned banners out before they reach the slider entity.
        $bannerRows = [];
        if (isset($data['banners']) && is_array($data['banners'])) {
            $bannerRows = $data['banners'];
            // The dynamicRows component nests its records one level deeper under
            // the same 'banners' key (submits banners[banners][...]). Unwrap to
            // the actual row list so each row's banner_id is read correctly.
            if (isset($bannerRows['banners']) && is_array($bannerRows['banners'])) {
                $bannerRows = $bannerRows['banners'];
            }
        }
        unset($data['banners']);

        $data = $this->normalizeData($data);

        try {
            if ($sliderId) {
                $slider = $this->sliderRepository->getById($sliderId);
            } else {
                $slider = $this->sliderFactory->create();
                unset($data['slider_id']);
            }

            $slider->addData($data);
            $this->sliderRepository->save($slider);

            $this->bannerSliderResource->saveAssignments((int)$slider->getId(), $bannerRows);

            $this->messageManager->addSuccessMessage(__('The slider has been saved.'));
            $this->dataPersistor->clear('etechflow_bannerslider_slider');

            if ($this->getRequest()->getParam('back')) {
                return $resultRedirect->setPath('*/*/edit', ['slider_id' => $slider->getId()]);
            }
            return $resultRedirect->setPath('*/*/');
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('This slider no longer exists.'));
            return $resultRedirect->setPath('*/*/');
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the slider.'));
            $this->dataPersistor->set('etechflow_bannerslider_slider', $data);
            return $resultRedirect->setPath('*/*/edit', ['slider_id' => $sliderId]);
        }
    }

    /**
     * Normalize multiselect arrays into the comma-separated form the model expects.
     *
     * @param array $data
     * @return array
     */
    private function normalizeData(array $data): array
    {
        foreach ([SliderInterface::STORE_IDS, SliderInterface::CUSTOMER_GROUP_IDS] as $key) {
            if (isset($data[$key]) && is_array($data[$key])) {
                $data[$key] = implode(',', $data[$key]);
            }
        }
        return $data;
    }
}
