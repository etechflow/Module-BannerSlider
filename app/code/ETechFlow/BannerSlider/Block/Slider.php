<?php
declare(strict_types=1);

namespace ETechFlow\BannerSlider\Block;

use ETechFlow\BannerSlider\Api\SliderRepositoryInterface;
use ETechFlow\BannerSlider\Model\Banner;
use ETechFlow\BannerSlider\Model\Slider as SliderModel;
use ETechFlow\BannerSlider\Model\Storefront\BannerProvider;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class Slider extends Template
{
    private const MEDIA_PATH = 'etechflow/bannerslider';
    private const CONFIG_ENABLED = 'etechflow_bannerslider/general/enabled';

    private ?SliderModel $slider = null;
    private bool $sliderLoaded = false;
    private ?array $banners = null;

    public function __construct(
        Context $context,
        private readonly SliderRepositoryInterface $sliderRepository,
        private readonly BannerProvider $bannerProvider,
        private readonly Json $json,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function isModuleEnabled(): bool
    {
        return $this->_scopeConfig->isSetFlag(
            self::CONFIG_ENABLED,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getSliderId(): int
    {
        return (int)$this->getData('slider_id');
    }

    public function getSlider(): ?SliderModel
    {
        if ($this->sliderLoaded) {
            return $this->slider;
        }
        $this->sliderLoaded = true;

        $sliderId = $this->getSliderId();
        if (!$sliderId) {
            return null;
        }
        try {
            /** @var SliderModel $slider */
            $slider = $this->sliderRepository->getById($sliderId);
            $this->slider = $slider->getStatus() ? $slider : null;
        } catch (NoSuchEntityException $e) {
            $this->slider = null;
        }
        return $this->slider;
    }

    /**
     * @return array<int, array{banner: Banner, variant: string, weight: int}>
     */
    public function getBanners(): array
    {
        if ($this->banners !== null) {
            return $this->banners;
        }
        $slider = $this->getSlider();
        if (!$slider) {
            $this->banners = [];
            return $this->banners;
        }
        $storeId = (int)$this->_storeManager->getStore()->getId();
        $this->banners = $this->bannerProvider->getActiveBanners((int)$slider->getId(), $storeId);
        return $this->banners;
    }

    public function hasBanners(): bool
    {
        return $this->getBanners() !== [];
    }

    public function getMediaUrl(?string $file): string
    {
        if (!$file) {
            return '';
        }
        $base = $this->_storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
        return $base . self::MEDIA_PATH . '/' . ltrim($file, '/');
    }

    /**
     * Unique DOM id so multiple sliders can coexist on a page.
     */
    public function getSliderHtmlId(): string
    {
        return 'etf-bannerslider-' . $this->getSliderId();
    }

    /**
     * JSON config consumed by the storefront slider JS component.
     */
    public function getJsConfig(): string
    {
        $slider = $this->getSlider();
        if (!$slider) {
            return '{}';
        }
        return $this->json->serialize([
            'autoplay' => $slider->getAutoplay(),
            'autoplaySpeed' => $slider->getAutoplaySpeed(),
            'animationSpeed' => $slider->getAnimationSpeed(),
            'effect' => $slider->getAnimationEffect(),
            'arrows' => $slider->getShowArrows(),
            'bullets' => $slider->getShowBullets(),
            'loop' => $slider->getIsLoop(),
            'pauseOnHover' => $slider->getPauseOnHover(),
            'lazyLoad' => $slider->getLazyLoad(),
            'sliderId' => (int)$slider->getId(),
        ]);
    }
}
