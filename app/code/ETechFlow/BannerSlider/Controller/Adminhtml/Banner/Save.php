<?php
declare(strict_types=1);

namespace ETechFlow\BannerSlider\Controller\Adminhtml\Banner;

use ETechFlow\BannerSlider\Api\Data\BannerInterface;
use ETechFlow\BannerSlider\Api\BannerRepositoryInterface;
use ETechFlow\BannerSlider\Model\BannerFactory;
use ETechFlow\BannerSlider\Model\Targeting\TargetingRules;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Catalog\Model\ImageUploader;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class Save extends Action
{
    public const ADMIN_RESOURCE = 'ETechFlow_BannerSlider::banner';

    /** Image fields that may carry an uploaded file to relocate from the tmp dir. */
    private const IMAGE_FIELDS = [BannerInterface::IMAGE, BannerInterface::IMAGE_MOBILE];

    public function __construct(
        Context $context,
        private readonly BannerRepositoryInterface $bannerRepository,
        private readonly BannerFactory $bannerFactory,
        private readonly DataPersistorInterface $dataPersistor,
        private readonly ImageUploader $imageUploader,
        private readonly TargetingRules $targetingRules
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

        $bannerId = isset($data['banner_id']) ? (int)$data['banner_id'] : 0;
        $data = $this->normalizeData($data);

        try {
            if ($bannerId) {
                $banner = $this->bannerRepository->getById($bannerId);
            } else {
                $banner = $this->bannerFactory->create();
                unset($data['banner_id']);
            }

            $banner->addData($data);
            $this->bannerRepository->save($banner);

            $this->messageManager->addSuccessMessage(__('The banner has been saved.'));
            $this->dataPersistor->clear('etechflow_bannerslider_banner');

            if ($this->getRequest()->getParam('back')) {
                return $resultRedirect->setPath('*/*/edit', ['banner_id' => $banner->getId()]);
            }
            return $resultRedirect->setPath('*/*/');
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('This banner no longer exists.'));
            return $resultRedirect->setPath('*/*/');
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the banner.'));
            $this->dataPersistor->set('etechflow_bannerslider_banner', $data);
            return $resultRedirect->setPath('*/*/edit', ['banner_id' => $bannerId]);
        }
    }

    /**
     * Flatten multiselect arrays and relocate freshly uploaded images from the tmp dir.
     *
     * @param array $data
     * @return array
     */
    private function normalizeData(array $data): array
    {
        foreach ([BannerInterface::STORE_IDS, BannerInterface::CUSTOMER_GROUP_IDS] as $key) {
            if (isset($data[$key]) && is_array($data[$key])) {
                $data[$key] = implode(',', $data[$key]);
            }
        }

        // Collapse the targeting fields into conditions_serialized, then drop
        // them — they are form-only, not table columns.
        $data[BannerInterface::CONDITIONS_SERIALIZED] = $this->targetingRules->toJson($data);
        foreach ($this->targetingRules->fieldNames() as $field) {
            unset($data[$field]);
        }

        foreach (self::IMAGE_FIELDS as $field) {
            $data[$field] = $this->resolveImageField($data[$field] ?? null, $field);
        }

        return $data;
    }

    /**
     * The imageUploader UI component submits an array; extract the file name and
     * move newly uploaded files out of the tmp directory.
     *
     * @param mixed $value
     * @param string $field
     * @return string|null
     */
    private function resolveImageField($value, string $field): ?string
    {
        if (is_array($value)) {
            $value = $value[0] ?? null;
            if (is_array($value)) {
                if (!empty($value['tmp_name']) || !empty($value['name'])) {
                    $name = $value['name'] ?? null;
                    if ($name) {
                        // Move from tmp to base path. For a freshly uploaded file this
                        // relocates it; for an already-persisted image it throws (not in
                        // tmp) and is caught, leaving the existing file in place.
                        try {
                            $this->imageUploader->moveFileFromTmp($name);
                        } catch (\Exception $e) {
                            // Already in place or nothing to move; keep the name.
                        }
                    }
                    return $name;
                }
                return null;
            }
        }
        return $value !== '' ? $value : null;
    }
}
