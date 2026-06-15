<?php
declare(strict_types=1);

namespace ETechFlow\BannerSlider\Model;

use ETechFlow\BannerSlider\Api\BannerRepositoryInterface;
use ETechFlow\BannerSlider\Api\Data\BannerInterface;
use ETechFlow\BannerSlider\Api\Data\BannerSearchResultsInterface;
use ETechFlow\BannerSlider\Api\Data\BannerSearchResultsInterfaceFactory;
use ETechFlow\BannerSlider\Model\ResourceModel\Banner as BannerResource;
use ETechFlow\BannerSlider\Model\ResourceModel\Banner\CollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class BannerRepository implements BannerRepositoryInterface
{
    /**
     * @var BannerInterface[]
     */
    private array $instances = [];

    public function __construct(
        private readonly BannerResource $resource,
        private readonly BannerFactory $bannerFactory,
        private readonly CollectionFactory $collectionFactory,
        private readonly BannerSearchResultsInterfaceFactory $searchResultsFactory,
        private readonly CollectionProcessorInterface $collectionProcessor
    ) {
    }

    public function save(BannerInterface $banner): BannerInterface
    {
        try {
            $this->resource->save($banner);
            unset($this->instances[(int)$banner->getId()]);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('Could not save the banner: %1', $e->getMessage()), $e);
        }
        return $banner;
    }

    public function getById(int $bannerId): BannerInterface
    {
        if (isset($this->instances[$bannerId])) {
            return $this->instances[$bannerId];
        }
        /** @var Banner $banner */
        $banner = $this->bannerFactory->create();
        $this->resource->load($banner, $bannerId);
        if (!$banner->getId()) {
            throw new NoSuchEntityException(__('Banner with id "%1" does not exist.', $bannerId));
        }
        $this->instances[$bannerId] = $banner;
        return $banner;
    }

    public function getList(SearchCriteriaInterface $searchCriteria): BannerSearchResultsInterface
    {
        $collection = $this->collectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);

        /** @var BannerSearchResultsInterface $results */
        $results = $this->searchResultsFactory->create();
        $results->setSearchCriteria($searchCriteria);
        $results->setItems($collection->getItems());
        $results->setTotalCount($collection->getSize());
        return $results;
    }

    public function delete(BannerInterface $banner): bool
    {
        try {
            $id = (int)$banner->getId();
            $this->resource->delete($banner);
            unset($this->instances[$id]);
        } catch (\Exception $e) {
            throw new CouldNotDeleteException(__('Could not delete the banner: %1', $e->getMessage()), $e);
        }
        return true;
    }

    public function deleteById(int $bannerId): bool
    {
        return $this->delete($this->getById($bannerId));
    }
}
