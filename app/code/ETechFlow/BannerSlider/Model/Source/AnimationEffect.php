<?php
declare(strict_types=1);

namespace ETechFlow\BannerSlider\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class AnimationEffect implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'slide', 'label' => __('Slide')],
            ['value' => 'fade', 'label' => __('Fade')],
            ['value' => 'cube', 'label' => __('Cube')],
        ];
    }
}
