<?php
declare(strict_types=1);

namespace ETechFlow\BannerSlider\Block;

use ETechFlow\BannerSlider\Api\SliderRepositoryInterface;
use ETechFlow\BannerSlider\Model\Banner;
use ETechFlow\BannerSlider\Model\Slider as SliderModel;
use ETechFlow\BannerSlider\Model\Storefront\BannerProvider;
use ETechFlow\BannerSlider\Model\Storefront\ProductBannerResolver;
use ETechFlow\BannerSlider\Model\Targeting\TargetingRules;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class Slider extends Template
{
    private const MEDIA_PATH = 'etechflow/bannerslider';
    private const CONFIG_ENABLED = 'etechflow_bannerslider/general/enabled';
    private const CONFIG_TRACKING = 'etechflow_bannerslider/performance/async_tracking';
    private const CONFIG_ATTRIBUTION = 'etechflow_bannerslider/analytics/attribution_window';

    private ?SliderModel $slider = null;
    private bool $sliderLoaded = false;
    private ?array $banners = null;

    public function __construct(
        Context $context,
        private readonly SliderRepositoryInterface $sliderRepository,
        private readonly BannerProvider $bannerProvider,
        private readonly ProductBannerResolver $productBannerResolver,
        private readonly TimezoneInterface $timezone,
        private readonly TargetingRules $targetingRules,
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

    /**
     * Whether storefront event tracking (impressions/clicks/add-to-cart) is on.
     */
    public function isTrackingEnabled(): bool
    {
        return $this->_scopeConfig->isSetFlag(
            self::CONFIG_TRACKING,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Days a banner click stays eligible to be credited with a later order.
     */
    public function getAttributionWindow(): int
    {
        $days = (int)$this->_scopeConfig->getValue(
            self::CONFIG_ATTRIBUTION,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        return $days > 0 ? $days : 7;
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
        $banners = $this->bannerProvider->getActiveBanners((int)$slider->getId(), $storeId);

        // A concluded A/B test serves only the winning variant. This is the
        // same for every visitor, so it can be filtered server-side and stay
        // Full Page Cache friendly; a still-running test renders all variants
        // and the browser picks one (see getJsConfig + slider.js).
        $winner = $slider->getAbWinner();
        if ($winner !== null) {
            $banners = array_values(array_filter(
                $banners,
                static fn ($row) => $row['variant'] === $winner
            ));
        }

        $this->banners = $banners;
        return $this->banners;
    }

    /**
     * Variant => traffic weight map for a running A/B test, derived from the
     * rendered banners (first weight seen per variant).
     *
     * @return array<string, int>
     */
    public function getAbVariants(): array
    {
        $map = [];
        foreach ($this->getBanners() as $row) {
            $variant = $row['variant'];
            if (!isset($map[$variant])) {
                $map[$variant] = max(1, (int)$row['weight']);
            }
        }
        return $map;
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
     * Build the data a video banner needs to render.
     *
     * For YouTube/Vimeo the iframe is created lazily by the JS (a "facade"),
     * so only the provider + embed URL are returned here; for self-hosted MP4
     * the file URL is resolved against the module media path.
     *
     * @param Banner $banner
     * @return array{provider:string,embed_url:string,src:string,autoplay:bool,muted:bool}
     */
    public function getVideoData(Banner $banner): array
    {
        $provider = $banner->getVideoType() ?: 'mp4';
        $raw = trim((string)$banner->getVideoUrl());
        $autoplay = $banner->getVideoAutoplay();
        $muted = $banner->getVideoMuted();

        $embedUrl = '';
        $src = '';
        if ($provider === 'youtube') {
            $id = $this->extractYoutubeId($raw);
            $params = ['rel' => '0', 'playsinline' => '1'];
            if ($autoplay) {
                $params['autoplay'] = '1';
            }
            if ($muted) {
                $params['mute'] = '1';
            }
            $embedUrl = $id ? 'https://www.youtube.com/embed/' . $id . '?' . http_build_query($params) : '';
        } elseif ($provider === 'vimeo') {
            $id = $this->extractVimeoId($raw);
            $params = [];
            if ($autoplay) {
                $params['autoplay'] = '1';
            }
            if ($muted) {
                $params['muted'] = '1';
            }
            $query = $params ? '?' . http_build_query($params) : '';
            $embedUrl = $id ? 'https://player.vimeo.com/video/' . $id . $query : '';
        } else {
            // MP4 — either an absolute URL or a file under the module media path.
            $src = preg_match('#^https?://#i', $raw) ? $raw : $this->getMediaUrl($raw);
            $provider = 'mp4';
        }

        return [
            'provider' => $provider,
            'embed_url' => $embedUrl,
            'src' => $src,
            'autoplay' => $autoplay,
            'muted' => $muted,
        ];
    }

    /**
     * Absolute target instant (epoch milliseconds, UTC) for a countdown banner
     * plus the hide-on-expiry flag, or null when no target is set. The client
     * compares this against its own clock, so the countdown is timezone-correct
     * regardless of where the visitor is.
     *
     * @param Banner $banner
     * @return array{ts:int,hide_expired:bool}|null
     */
    public function getCountdownData(Banner $banner): ?array
    {
        $target = $banner->getCountdownTo();
        if (!$target) {
            return null;
        }
        try {
            // The stored value is interpreted in the configured store timezone.
            $date = $this->timezone->date($target);
        } catch (\Exception $e) {
            return null;
        }
        return [
            'ts' => $date->getTimestamp() * 1000,
            'hide_expired' => $banner->getCountdownHideExpired(),
        ];
    }

    /**
     * Render-ready product data for a product banner, or null when the product
     * is unavailable (so the slide is skipped).
     *
     * @param Banner $banner
     * @return array|null
     */
    public function getProductData(Banner $banner): ?array
    {
        $productId = $banner->getProductId();
        if (!$productId) {
            return null;
        }
        $storeId = (int)$this->_storeManager->getStore()->getId();
        return $this->productBannerResolver->resolve($productId, $storeId);
    }

    /**
     * Client-side targeting rules for a banner as a JSON string, or '' when the
     * banner has no targeting. Merges the stored condition JSON with the
     * customer-group selection so the browser evaluates everything together.
     *
     * @param Banner $banner
     * @return string
     */
    public function getTargetingJson(Banner $banner): string
    {
        $rules = $this->targetingRules->decode($banner->getConditionsSerialized());

        $groupIds = $banner->getCustomerGroupIds();
        if ($groupIds) {
            $rules['groups'] = array_values($groupIds);
        }

        return $rules ? $this->json->serialize($rules) : '';
    }

    /**
     * Extract the 11-char video id from any common YouTube URL form, or return
     * the input unchanged when it is already a bare id.
     *
     * @param string $value
     * @return string
     */
    private function extractYoutubeId(string $value): string
    {
        if (preg_match('#(?:youtu\.be/|v/|embed/|watch\?v=|&v=)([A-Za-z0-9_-]{11})#', $value, $m)) {
            return $m[1];
        }
        return preg_match('#^[A-Za-z0-9_-]{11}$#', $value) ? $value : '';
    }

    /**
     * Extract the numeric id from a Vimeo URL, or return a bare numeric id.
     *
     * @param string $value
     * @return string
     */
    private function extractVimeoId(string $value): string
    {
        if (preg_match('#vimeo\.com/(?:video/)?(\d+)#', $value, $m)) {
            return $m[1];
        }
        return preg_match('#^\d+$#', $value) ? $value : '';
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
        $abVariants = $this->getAbVariants();
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
            'track' => $this->isTrackingEnabled(),
            'trackUrl' => $this->getUrl('bannerslider/track/hit'),
            'attributionDays' => $this->getAttributionWindow(),
            'abTest' => $slider->getIsAbTest() && $slider->getAbWinner() === null && count($abVariants) >= 2,
            'abVariants' => $abVariants,
        ]);
    }
}
