<?php
declare(strict_types=1);

namespace ETechFlow\BannerSlider\Model\Storefront;

use ETechFlow\BannerSlider\Api\Data\BannerInterface;
use ETechFlow\BannerSlider\Model\Banner;
use ETechFlow\BannerSlider\Model\ResourceModel\Banner\CollectionFactory as BannerCollectionFactory;
use ETechFlow\BannerSlider\Model\ResourceModel\BannerSlider as BannerSliderResource;
use ETechFlow\BannerSlider\Model\Source\Status;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

/**
 * Resolves the ordered, currently-active banners for a slider.
 *
 * Only store-, status- and schedule-based filtering happens here so the result
 * is identical for every visitor of a given store at a given time — keeping the
 * rendered slider Full Page Cache friendly. Per-customer targeting (groups,
 * cart, geo) is layered on later via customer-data sections (Phase 6).
 */
class BannerProvider
{
    public function __construct(
        private readonly BannerSliderResource $bannerSliderResource,
        private readonly BannerCollectionFactory $bannerCollectionFactory,
        private readonly TimezoneInterface $timezone
    ) {
    }

    /**
     * @param int $sliderId
     * @param int $storeId
     * @return array<int, array{banner: Banner, variant: string, weight: int}>
     */
    public function getActiveBanners(int $sliderId, int $storeId): array
    {
        $assignments = $this->bannerSliderResource->getAssignments($sliderId);
        if (!$assignments) {
            return [];
        }

        $bannerIds = array_map(static fn ($a) => $a['banner_id'], $assignments);

        $collection = $this->bannerCollectionFactory->create();
        $collection->addFieldToFilter('banner_id', ['in' => $bannerIds])
            ->addFieldToFilter('status', Status::ENABLED);

        /** @var array<int, Banner> $byId */
        $byId = [];
        foreach ($collection as $banner) {
            if ($this->isScheduledNow($banner)
                && $this->isInStore($banner, $storeId)
                && !$this->isExpiredCountdown($banner)
            ) {
                $byId[(int)$banner->getId()] = $banner;
            }
        }

        // Preserve the slider's explicit ordering and carry variant/weight through.
        $result = [];
        foreach ($assignments as $assignment) {
            $id = $assignment['banner_id'];
            if (isset($byId[$id])) {
                $result[] = [
                    'banner' => $byId[$id],
                    'variant' => $assignment['variant'],
                    'weight' => $assignment['weight'],
                ];
            }
        }
        return $result;
    }

    private function isScheduledNow(Banner $banner): bool
    {
        $today = $this->timezone->date()->format('Y-m-d');
        $from = $banner->getFromDate();
        $to = $banner->getToDate();

        if ($from && $from > $today) {
            return false;
        }
        if ($to && $to < $today) {
            return false;
        }
        return true;
    }

    /**
     * Keep an expired countdown banner out of the (cacheable) markup when the
     * admin chose to hide it on expiry. Non-countdown banners are unaffected.
     *
     * @param Banner $banner
     * @return bool
     */
    private function isExpiredCountdown(Banner $banner): bool
    {
        if ($banner->getType() !== BannerInterface::TYPE_COUNTDOWN || !$banner->getCountdownHideExpired()) {
            return false;
        }
        $target = $banner->getCountdownTo();
        if (!$target) {
            return false;
        }
        try {
            $targetTs = $this->timezone->date($target)->getTimestamp();
        } catch (\Exception $e) {
            return false;
        }
        return $targetTs <= $this->timezone->date()->getTimestamp();
    }

    private function isInStore(Banner $banner, int $storeId): bool
    {
        $storeIds = $banner->getStoreIds();
        // Empty or "0" (All Store Views) means visible everywhere.
        if (!$storeIds || in_array(0, $storeIds, true)) {
            return true;
        }
        return in_array($storeId, $storeIds, true);
    }
}
