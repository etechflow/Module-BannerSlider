<?php
declare(strict_types=1);

namespace ETechFlow\BannerSliderHyva\Plugin\Widget;

use ETechFlow\BannerSlider\Block\Widget\Slider as CoreWidget;
use ETechFlow\BannerSliderHyva\Block\Widget\Slider as HyvaWidget;
use Magento\Framework\View\DesignInterface;

/**
 * Makes the core "ETechFlow Banner Slider" widget render the Hyvä (Alpine)
 * template automatically when a Hyvä-based theme is active.
 *
 * Merchants on Hyvä often pick the plain "ETechFlow Banner Slider" widget by
 * mistake; the Luma renderer needs RequireJS and shows nothing on Hyvä. This
 * plugin removes that foot-gun: on a Hyvä theme the core widget delegates to the
 * same Hyvä template the dedicated "(Hyvä)" widget uses, so either choice works.
 * On Luma themes it does nothing, preserving the original Luma rendering.
 */
class HyvaTemplateResolver
{
    private const HYVA_TEMPLATE = 'ETechFlow_BannerSliderHyva::widget/slider.phtml';

    public function __construct(
        private readonly DesignInterface $design
    ) {
    }

    /**
     * @param CoreWidget $subject
     * @return void
     */
    public function beforeToHtml(CoreWidget $subject): void
    {
        // The dedicated Hyvä widget already sets its own template — leave it alone.
        if ($subject instanceof HyvaWidget) {
            return;
        }
        // Respect a template that was explicitly set elsewhere.
        if ($subject->getTemplate()) {
            return;
        }
        if ($this->isHyvaTheme()) {
            $subject->setTemplate(self::HYVA_TEMPLATE);
        }
    }

    /**
     * True when the active theme (or any ancestor) is a Hyvä theme.
     *
     * @return bool
     */
    private function isHyvaTheme(): bool
    {
        $theme = $this->design->getDesignTheme();
        while ($theme) {
            if (stripos((string)$theme->getCode(), 'hyva') !== false) {
                return true;
            }
            $theme = $theme->getParentTheme();
        }
        return false;
    }
}
