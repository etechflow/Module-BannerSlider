<?php
declare(strict_types=1);

namespace ETechFlow\BannerSlider\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class AbGoal implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'ctr', 'label' => __('Click-Through Rate (CTR)')],
            ['value' => 'add_to_cart', 'label' => __('Add to Cart')],
            ['value' => 'revenue', 'label' => __('Revenue')],
        ];
    }
}
