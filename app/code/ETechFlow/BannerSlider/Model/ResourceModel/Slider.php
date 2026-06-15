<?php
declare(strict_types=1);

namespace ETechFlow\BannerSlider\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Slider extends AbstractDb
{
    public const TABLE_NAME = 'etechflow_bannerslider_slider';
    public const LINK_TABLE = 'etechflow_bannerslider_banner_slider';

    protected function _construct(): void
    {
        $this->_init(self::TABLE_NAME, 'slider_id');
    }
}
