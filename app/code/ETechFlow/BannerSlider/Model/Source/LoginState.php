<?php
declare(strict_types=1);

namespace ETechFlow\BannerSlider\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class LoginState implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'any', 'label' => __('Any visitor')],
            ['value' => 'logged_in', 'label' => __('Logged-in customers only')],
            ['value' => 'guest', 'label' => __('Guests only')],
        ];
    }
}
