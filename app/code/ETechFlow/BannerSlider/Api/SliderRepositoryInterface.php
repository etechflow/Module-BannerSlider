<?php
declare(strict_types=1);

namespace ETechFlow\BannerSlider\Api;

use ETechFlow\BannerSlider\Api\Data\SliderInterface;
use ETechFlow\BannerSlider\Api\Data\SliderSearchResultsInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * @api
 */
interface SliderRepositoryInterface
{
    /**
     * Save slider.
     *
     * @param \ETechFlow\BannerSlider\Api\Data\SliderInterface $slider
     * @return \ETechFlow\BannerSlider\Api\Data\SliderInterface
     * @throws CouldNotSaveException
     */
    public function save(SliderInterface $slider): SliderInterface;

    /**
     * Get slider by id.
     *
     * @param int $sliderId
     * @return \ETechFlow\BannerSlider\Api\Data\SliderInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $sliderId): SliderInterface;

    /**
     * Retrieve sliders matching the search criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \ETechFlow\BannerSlider\Api\Data\SliderSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SliderSearchResultsInterface;

    /**
     * Delete slider.
     *
     * @param \ETechFlow\BannerSlider\Api\Data\SliderInterface $slider
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(SliderInterface $slider): bool;

    /**
     * Delete slider by id.
     *
     * @param int $sliderId
     * @return bool
     * @throws NoSuchEntityException
     * @throws CouldNotDeleteException
     */
    public function deleteById(int $sliderId): bool;
}
