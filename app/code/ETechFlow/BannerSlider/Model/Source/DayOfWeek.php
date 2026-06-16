<?php
declare(strict_types=1);

namespace ETechFlow\BannerSlider\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Days of the week keyed to JavaScript's Date.getDay() (0 = Sunday).
 */
class DayOfWeek implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => '1', 'label' => __('Monday')],
            ['value' => '2', 'label' => __('Tuesday')],
            ['value' => '3', 'label' => __('Wednesday')],
            ['value' => '4', 'label' => __('Thursday')],
            ['value' => '5', 'label' => __('Friday')],
            ['value' => '6', 'label' => __('Saturday')],
            ['value' => '0', 'label' => __('Sunday')],
        ];
    }
}
