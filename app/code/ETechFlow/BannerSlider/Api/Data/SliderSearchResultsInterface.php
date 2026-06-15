<?php
declare(strict_types=1);

namespace ETechFlow\BannerSlider\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * @api
 */
interface SliderSearchResultsInterface extends SearchResultsInterface
{
    /**
     * @return \ETechFlow\BannerSlider\Api\Data\SliderInterface[]
     */
    public function getItems();

    /**
     * @param \ETechFlow\BannerSlider\Api\Data\SliderInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
