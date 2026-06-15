<?php
declare(strict_types=1);

namespace ETechFlow\BannerSlider\Model\ResourceModel\Slider;

use ETechFlow\BannerSlider\Model\ResourceModel\Slider as SliderResource;
use ETechFlow\BannerSlider\Model\Slider as SliderModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'slider_id';

    protected function _construct(): void
    {
        $this->_init(SliderModel::class, SliderResource::class);
    }
}
