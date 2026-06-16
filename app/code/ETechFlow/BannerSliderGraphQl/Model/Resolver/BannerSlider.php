<?php
declare(strict_types=1);

namespace ETechFlow\BannerSliderGraphQl\Model\Resolver;

use ETechFlow\BannerSlider\Api\SliderRepositoryInterface;
use ETechFlow\BannerSlider\Model\Banner;
use ETechFlow\BannerSlider\Model\Config;
use ETechFlow\BannerSlider\Model\Storefront\BannerProvider;
use ETechFlow\BannerSlider\Model\Targeting\TargetingRules;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Resolves the `etechflowBannerSlider` query for headless / PWA / Hyvä Checkout.
 *
 * Returns every active banner for the store (status/schedule/store filtered —
 * the same for all visitors, so the payload is safe to cache) together with the
 * targeting rules, variant and weight, leaving per-visitor targeting and A/B
 * selection to the client. A concluded A/B test is collapsed to its winner
 * server-side.
 */
class BannerSlider implements ResolverInterface
{
    private const MEDIA_PATH = 'etechflow/bannerslider/';
    private const XML_ENABLED = 'etechflow_bannerslider/general/enabled';
    private const XML_TRACKING = 'etechflow_bannerslider/performance/async_tracking';
    private const XML_ATTRIBUTION = 'etechflow_bannerslider/analytics/attribution_window';

    public function __construct(
        private readonly SliderRepositoryInterface $sliderRepository,
        private readonly BannerProvider $bannerProvider,
        private readonly TargetingRules $targetingRules,
        private readonly TimezoneInterface $timezone,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly Config $config,
        private readonly Json $json
    ) {
    }

    /**
     * @inheritDoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, ?array $value = null, ?array $args = null)
    {
        $sliderId = (int)($args['slider_id'] ?? 0);
        if ($sliderId <= 0) {
            throw new GraphQlInputException(__('A valid "slider_id" is required.'));
        }

        $store = $context->getExtensionAttributes()->getStore();
        $storeId = $store instanceof StoreInterface ? (int)$store->getId() : 0;

        // Gated on a valid license + admin toggle (Model\Config).
        if (!$this->config->isEnabled()) {
            return null;
        }

        try {
            $slider = $this->sliderRepository->getById($sliderId);
        } catch (NoSuchEntityException $e) {
            return null;
        }
        if (!$slider->getStatus()) {
            return null;
        }

        $rows = $this->bannerProvider->getActiveBanners($sliderId, $storeId);
        $winner = $slider->getAbWinner();
        if ($winner !== null) {
            $rows = array_values(array_filter($rows, static fn ($row) => $row['variant'] === $winner));
        }

        $banners = [];
        foreach ($rows as $row) {
            $banners[] = $this->mapBanner($row, $store, $storeId);
        }

        return [
            'slider_id' => (int)$slider->getId(),
            'title' => $slider->getTitle(),
            'autoplay' => $slider->getAutoplay(),
            'autoplay_speed' => $slider->getAutoplaySpeed(),
            'animation_speed' => $slider->getAnimationSpeed(),
            'animation_effect' => $slider->getAnimationEffect(),
            'show_arrows' => $slider->getShowArrows(),
            'show_bullets' => $slider->getShowBullets(),
            'is_loop' => $slider->getIsLoop(),
            'pause_on_hover' => $slider->getPauseOnHover(),
            'lazy_load' => $slider->getLazyLoad(),
            'is_ab_test' => $slider->getIsAbTest(),
            'ab_goal' => $slider->getAbGoal(),
            'ab_winner' => $winner,
            'track' => $this->scopeConfig->isSetFlag(self::XML_TRACKING, ScopeInterface::SCOPE_STORE, $storeId),
            'attribution_days' => (int)$this->scopeConfig->getValue(self::XML_ATTRIBUTION, ScopeInterface::SCOPE_STORE, $storeId) ?: 7,
            'banners' => $banners,
        ];
    }

    /**
     * @param array{banner:Banner,variant:string,weight:int} $row
     * @param StoreInterface $store
     * @param int $storeId
     * @return array<string, mixed>
     */
    private function mapBanner(array $row, StoreInterface $store, int $storeId): array
    {
        /** @var Banner $banner */
        $banner = $row['banner'];
        $mediaBase = $store->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . self::MEDIA_PATH;

        return [
            'banner_id' => (int)$banner->getId(),
            'type' => $banner->getType(),
            'name' => $banner->getName(),
            'image' => $banner->getImage() ? $mediaBase . ltrim($banner->getImage(), '/') : null,
            'image_mobile' => $banner->getImageMobile() ? $mediaBase . ltrim($banner->getImageMobile(), '/') : null,
            'alt_text' => $banner->getAltText(),
            'url' => $banner->getUrl(),
            'open_new_tab' => $banner->getOpenNewTab(),
            'video_type' => $banner->getVideoType(),
            'video_url' => $banner->getVideoUrl(),
            'video_autoplay' => $banner->getVideoAutoplay(),
            'video_muted' => $banner->getVideoMuted(),
            'content' => $banner->getContent(),
            'product_id' => $banner->getProductId(),
            'product_sku' => $this->resolveSku($banner, $storeId),
            'countdown_to' => $banner->getCountdownTo(),
            'countdown_at_ms' => $this->countdownMs($banner->getCountdownTo()),
            'countdown_hide_expired' => $banner->getCountdownHideExpired(),
            'variant' => $row['variant'],
            'weight' => (int)$row['weight'],
            'targeting' => $this->buildTargeting($banner),
        ];
    }

    /**
     * @param Banner $banner
     * @param int $storeId
     * @return string|null
     */
    private function resolveSku(Banner $banner, int $storeId): ?string
    {
        if ($banner->getType() !== Banner::TYPE_PRODUCT || !$banner->getProductId()) {
            return null;
        }
        try {
            return $this->productRepository->getById((int)$banner->getProductId(), false, $storeId)->getSku();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @param string|null $countdownTo
     * @return float|null
     */
    private function countdownMs(?string $countdownTo): ?float
    {
        if (!$countdownTo) {
            return null;
        }
        try {
            return (float)($this->timezone->date($countdownTo)->getTimestamp() * 1000);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Merge stored condition JSON with the customer-group selection, mirroring
     * the storefront block so the client evaluates the same rules.
     *
     * @param Banner $banner
     * @return string
     */
    private function buildTargeting(Banner $banner): string
    {
        $rules = $this->targetingRules->decode($banner->getConditionsSerialized());
        $groups = $banner->getCustomerGroupIds();
        if ($groups) {
            $rules['groups'] = array_values($groups);
        }
        return $rules ? $this->json->serialize($rules) : '';
    }
}
