<?php
declare(strict_types=1);

namespace ETechFlow\BannerSliderHyva\Block\Widget;

use ETechFlow\BannerSlider\Block\Slider as CoreSlider;
use Magento\Widget\Block\BlockInterface;

/**
 * Hyvä (Tailwind + Alpine) banner slider widget.
 *
 * Reuses every data accessor from the core slider block (banners, video /
 * countdown / product data, targeting JSON, A/B variants, JS config) and only
 * swaps in a Hyvä template + Alpine runtime, so the storefront logic stays in
 * one place. Placed via Content > Widgets ("ETechFlow Banner Slider (Hyvä)").
 */
class Slider extends CoreSlider implements BlockInterface
{
    protected function _toHtml(): string
    {
        if (!$this->getTemplate()) {
            $this->setTemplate('ETechFlow_BannerSliderHyva::widget/slider.phtml');
        }
        return parent::_toHtml();
    }

    // getSectionUrl() now lives on the shared core block (ETechFlow\BannerSlider\Block\Slider)
    // so the core "ETechFlow Banner Slider" widget can render this Hyvä template too.
}
