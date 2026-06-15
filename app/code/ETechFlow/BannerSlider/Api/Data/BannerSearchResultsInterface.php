<?php
declare(strict_types=1);

namespace ETechFlow\BannerSlider\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * @api
 */
interface BannerSearchResultsInterface extends SearchResultsInterface
{
    /**
     * @return \ETechFlow\BannerSlider\Api\Data\BannerInterface[]
     */
    public function getItems();

    /**
     * @param \ETechFlow\BannerSlider\Api\Data\BannerInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
