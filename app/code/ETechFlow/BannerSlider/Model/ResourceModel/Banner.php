<?php
declare(strict_types=1);

namespace ETechFlow\BannerSlider\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Banner extends AbstractDb
{
    public const TABLE_NAME = 'etechflow_bannerslider_banner';

    protected function _construct(): void
    {
        $this->_init(self::TABLE_NAME, 'banner_id');
    }
}
