<?php
declare(strict_types=1);

namespace ETechFlow\BannerSlider\Api;

use ETechFlow\BannerSlider\Api\Data\BannerInterface;
use ETechFlow\BannerSlider\Api\Data\BannerSearchResultsInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * @api
 */
interface BannerRepositoryInterface
{
    /**
     * Save banner.
     *
     * @param \ETechFlow\BannerSlider\Api\Data\BannerInterface $banner
     * @return \ETechFlow\BannerSlider\Api\Data\BannerInterface
     * @throws CouldNotSaveException
     */
    public function save(BannerInterface $banner): BannerInterface;

    /**
     * Get banner by id.
     *
     * @param int $bannerId
     * @return \ETechFlow\BannerSlider\Api\Data\BannerInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $bannerId): BannerInterface;

    /**
     * Retrieve banners matching the search criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \ETechFlow\BannerSlider\Api\Data\BannerSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): BannerSearchResultsInterface;

    /**
     * Delete banner.
     *
     * @param \ETechFlow\BannerSlider\Api\Data\BannerInterface $banner
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(BannerInterface $banner): bool;

    /**
     * Delete banner by id.
     *
     * @param int $bannerId
     * @return bool
     * @throws NoSuchEntityException
     * @throws CouldNotDeleteException
     */
    public function deleteById(int $bannerId): bool;
}
