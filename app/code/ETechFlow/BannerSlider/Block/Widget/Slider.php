<?php
declare(strict_types=1);

namespace ETechFlow\BannerSlider\Block\Widget;

use ETechFlow\BannerSlider\Block\Slider as SliderBlock;
use Magento\Widget\Block\BlockInterface;

/**
 * Widget wrapper around the storefront slider block.
 * Placed via Content > Widgets ("ETechFlow Banner Slider").
 */
class Slider extends SliderBlock implements BlockInterface
{
    protected function _toHtml(): string
    {
        // Render through the shared slider template.
        if (!$this->getTemplate()) {
            $this->setTemplate('ETechFlow_BannerSlider::slider.phtml');
        }
        return parent::_toHtml();
    }
}
