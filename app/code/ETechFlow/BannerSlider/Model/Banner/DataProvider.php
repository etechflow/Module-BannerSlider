<?php
declare(strict_types=1);

namespace ETechFlow\BannerSlider\Model\Banner;

use ETechFlow\BannerSlider\Api\Data\BannerInterface;
use ETechFlow\BannerSlider\Model\ResourceModel\Banner\Collection;
use ETechFlow\BannerSlider\Model\ResourceModel\Banner\CollectionFactory;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;

class DataProvider extends AbstractDataProvider
{
    private const IMAGE_BASE_PATH = 'etechflow/bannerslider';

    /**
     * @var Collection
     */
    protected $collection;

    /**
     * @var array|null
     */
    private ?array $loadedData = null;

    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        CollectionFactory $collectionFactory,
        private readonly DataPersistorInterface $dataPersistor,
        private readonly StoreManagerInterface $storeManager,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    public function getData(): array
    {
        if ($this->loadedData !== null) {
            return $this->loadedData;
        }

        $this->loadedData = [];
        /** @var \ETechFlow\BannerSlider\Model\Banner $banner */
        foreach ($this->collection->getItems() as $banner) {
            $data = $banner->getData();
            $data[BannerInterface::STORE_IDS] = $banner->getStoreIds();
            $data[BannerInterface::CUSTOMER_GROUP_IDS] = $banner->getCustomerGroupIds();

            foreach ([BannerInterface::IMAGE, BannerInterface::IMAGE_MOBILE] as $field) {
                $data[$field] = $this->buildImagePreview($banner->getData($field));
            }
            $this->loadedData[$banner->getId()] = $data;
        }

        $persisted = $this->dataPersistor->get('etechflow_bannerslider_banner');
        if (!empty($persisted)) {
            $bannerId = $persisted['banner_id'] ?? null;
            $this->loadedData[$bannerId] = $persisted;
            $this->dataPersistor->clear('etechflow_bannerslider_banner');
        }

        return $this->loadedData;
    }

    /**
     * Convert a stored image filename into the array shape the imageUploader UI expects.
     *
     * @param string|null $fileName
     * @return array
     */
    private function buildImagePreview(?string $fileName): array
    {
        if (!$fileName) {
            return [];
        }
        $mediaUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
        return [
            [
                'name' => $fileName,
                'url' => $mediaUrl . self::IMAGE_BASE_PATH . '/' . ltrim($fileName, '/'),
            ],
        ];
    }
}
