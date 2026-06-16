<?php
declare(strict_types=1);

namespace ETechFlow\BannerSlider\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class TargetDevice implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'mobile', 'label' => __('Mobile')],
            ['value' => 'tablet', 'label' => __('Tablet')],
            ['value' => 'desktop', 'label' => __('Desktop')],
        ];
    }
}
