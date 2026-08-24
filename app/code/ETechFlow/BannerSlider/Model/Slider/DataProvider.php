<?php
declare(strict_types=1);

namespace ETechFlow\BannerSlider\Model\Slider;

use ETechFlow\BannerSlider\Api\Data\SliderInterface;
use ETechFlow\BannerSlider\Model\ResourceModel\BannerSlider as BannerSliderResource;
use ETechFlow\BannerSlider\Model\ResourceModel\Slider\Collection;
use ETechFlow\BannerSlider\Model\ResourceModel\Slider\CollectionFactory;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;

class DataProvider extends AbstractDataProvider
{
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
        private readonly BannerSliderResource $bannerSliderResource,
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
        /** @var \ETechFlow\BannerSlider\Model\Slider $slider */
        foreach ($this->collection->getItems() as $slider) {
            $data = $slider->getData();
            // Expand csv fields into string arrays for multiselect ui components.
            // Option values are strings, so an int value would leave the saved
            // selection un-highlighted on reopen (reads as "not saving").
            $data[SliderInterface::STORE_IDS] = array_map('strval', $slider->getStoreIds());
            $data[SliderInterface::CUSTOMER_GROUP_IDS] = array_map('strval', $slider->getCustomerGroupIds());
            // The banners dynamicRows binds one level deeper (data.banners.banners
            // — its name doubles the scope), so nest the saved rows the same way or
            // the grid loads EMPTY on edit and a re-save silently drops every banner.
            $data['banners'] = ['banners' => $this->bannerSliderResource->getAssignments((int)$slider->getId())];
            $this->loadedData[$slider->getId()] = $data;
        }

        $persisted = $this->dataPersistor->get('etechflow_bannerslider_slider');
        if (!empty($persisted)) {
            $sliderId = $persisted['slider_id'] ?? null;
            $this->loadedData[$sliderId] = $persisted;
            $this->dataPersistor->clear('etechflow_bannerslider_slider');
        }

        return $this->loadedData;
    }
}
