<?php
declare(strict_types=1);

namespace ETechFlow\BannerSlider\Model\ResourceModel\Banner;

use ETechFlow\BannerSlider\Model\ResourceModel\Banner as BannerResource;
use ETechFlow\BannerSlider\Model\Banner as BannerModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'banner_id';

    protected function _construct(): void
    {
        $this->_init(BannerModel::class, BannerResource::class);
    }
}
