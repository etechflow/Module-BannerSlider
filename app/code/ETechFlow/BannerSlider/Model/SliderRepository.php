<?php
declare(strict_types=1);

namespace ETechFlow\BannerSlider\Model;

use ETechFlow\BannerSlider\Api\Data\SliderInterface;
use ETechFlow\BannerSlider\Api\Data\SliderSearchResultsInterface;
use ETechFlow\BannerSlider\Api\Data\SliderSearchResultsInterfaceFactory;
use ETechFlow\BannerSlider\Api\SliderRepositoryInterface;
use ETechFlow\BannerSlider\Model\ResourceModel\Slider as SliderResource;
use ETechFlow\BannerSlider\Model\ResourceModel\Slider\CollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class SliderRepository implements SliderRepositoryInterface
{
    /**
     * @var SliderInterface[]
     */
    private array $instances = [];

    public function __construct(
        private readonly SliderResource $resource,
        private readonly SliderFactory $sliderFactory,
        private readonly CollectionFactory $collectionFactory,
        private readonly SliderSearchResultsInterfaceFactory $searchResultsFactory,
        private readonly CollectionProcessorInterface $collectionProcessor
    ) {
    }

    public function save(SliderInterface $slider): SliderInterface
    {
        try {
            $this->resource->save($slider);
            unset($this->instances[(int)$slider->getId()]);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('Could not save the slider: %1', $e->getMessage()), $e);
        }
        return $slider;
    }

    public function getById(int $sliderId): SliderInterface
    {
        if (isset($this->instances[$sliderId])) {
            return $this->instances[$sliderId];
        }
        /** @var Slider $slider */
        $slider = $this->sliderFactory->create();
        $this->resource->load($slider, $sliderId);
        if (!$slider->getId()) {
            throw new NoSuchEntityException(__('Slider with id "%1" does not exist.', $sliderId));
        }
        $this->instances[$sliderId] = $slider;
        return $slider;
    }

    public function getList(SearchCriteriaInterface $searchCriteria): SliderSearchResultsInterface
    {
        $collection = $this->collectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);

        /** @var SliderSearchResultsInterface $results */
        $results = $this->searchResultsFactory->create();
        $results->setSearchCriteria($searchCriteria);
        $results->setItems($collection->getItems());
        $results->setTotalCount($collection->getSize());
        return $results;
    }

    public function delete(SliderInterface $slider): bool
    {
        try {
            $id = (int)$slider->getId();
            $this->resource->delete($slider);
            unset($this->instances[$id]);
        } catch (\Exception $e) {
            throw new CouldNotDeleteException(__('Could not delete the slider: %1', $e->getMessage()), $e);
        }
        return true;
    }

    public function deleteById(int $sliderId): bool
    {
        return $this->delete($this->getById($sliderId));
    }
}
