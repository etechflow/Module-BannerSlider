<?php
declare(strict_types=1);

namespace ETechFlow\BannerSlider\Model\Source;

use ETechFlow\BannerSlider\Api\Data\BannerInterface;
use Magento\Framework\Data\OptionSourceInterface;

class BannerType implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => BannerInterface::TYPE_IMAGE, 'label' => __('Image')],
            ['value' => BannerInterface::TYPE_VIDEO, 'label' => __('Video')],
            ['value' => BannerInterface::TYPE_HTML, 'label' => __('HTML / Rich Content')],
            ['value' => BannerInterface::TYPE_PRODUCT, 'label' => __('Product')],
            ['value' => BannerInterface::TYPE_COUNTDOWN, 'label' => __('Countdown Timer')],
        ];
    }
}
