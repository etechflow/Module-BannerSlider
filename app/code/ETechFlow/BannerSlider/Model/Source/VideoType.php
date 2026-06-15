<?php
declare(strict_types=1);

namespace ETechFlow\BannerSlider\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class VideoType implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'mp4', 'label' => __('MP4 (self-hosted)')],
            ['value' => 'youtube', 'label' => __('YouTube')],
            ['value' => 'vimeo', 'label' => __('Vimeo')],
        ];
    }
}
