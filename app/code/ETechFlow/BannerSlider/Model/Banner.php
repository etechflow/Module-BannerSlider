<?php
declare(strict_types=1);

namespace ETechFlow\BannerSlider\Model;

use ETechFlow\BannerSlider\Api\Data\BannerInterface;
use ETechFlow\BannerSlider\Model\ResourceModel\Banner as BannerResource;
use Magento\Framework\Model\AbstractModel;

class Banner extends AbstractModel implements BannerInterface
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'etechflow_bannerslider_banner';

    protected function _construct(): void
    {
        $this->_init(BannerResource::class);
    }

    public function getName(): ?string
    {
        $value = $this->getData(self::NAME);
        return $value === null ? null : (string)$value;
    }

    public function setName(string $name): BannerInterface
    {
        return $this->setData(self::NAME, $name);
    }

    public function getStatus(): int
    {
        return (int)$this->getData(self::STATUS);
    }

    public function setStatus(int $status): BannerInterface
    {
        return $this->setData(self::STATUS, $status);
    }

    public function getType(): string
    {
        return (string)($this->getData(self::TYPE) ?: self::TYPE_IMAGE);
    }

    public function setType(string $type): BannerInterface
    {
        return $this->setData(self::TYPE, $type);
    }

    public function getImage(): ?string
    {
        $value = $this->getData(self::IMAGE);
        return $value === null ? null : (string)$value;
    }

    public function setImage(?string $image): BannerInterface
    {
        return $this->setData(self::IMAGE, $image);
    }

    public function getImageMobile(): ?string
    {
        $value = $this->getData(self::IMAGE_MOBILE);
        return $value === null ? null : (string)$value;
    }

    public function setImageMobile(?string $image): BannerInterface
    {
        return $this->setData(self::IMAGE_MOBILE, $image);
    }

    public function getAltText(): ?string
    {
        $value = $this->getData(self::ALT_TEXT);
        return $value === null ? null : (string)$value;
    }

    public function setAltText(?string $altText): BannerInterface
    {
        return $this->setData(self::ALT_TEXT, $altText);
    }

    public function getUrl(): ?string
    {
        $value = $this->getData(self::URL);
        return $value === null ? null : (string)$value;
    }

    public function setUrl(?string $url): BannerInterface
    {
        return $this->setData(self::URL, $url);
    }

    public function getOpenNewTab(): bool
    {
        return (bool)$this->getData(self::OPEN_NEW_TAB);
    }

    public function setOpenNewTab(bool $openNewTab): BannerInterface
    {
        return $this->setData(self::OPEN_NEW_TAB, $openNewTab ? 1 : 0);
    }

    public function getVideoType(): ?string
    {
        $value = $this->getData(self::VIDEO_TYPE);
        return $value === null ? null : (string)$value;
    }

    public function setVideoType(?string $videoType): BannerInterface
    {
        return $this->setData(self::VIDEO_TYPE, $videoType);
    }

    public function getVideoUrl(): ?string
    {
        $value = $this->getData(self::VIDEO_URL);
        return $value === null ? null : (string)$value;
    }

    public function setVideoUrl(?string $videoUrl): BannerInterface
    {
        return $this->setData(self::VIDEO_URL, $videoUrl);
    }

    public function getVideoAutoplay(): bool
    {
        return (bool)$this->getData(self::VIDEO_AUTOPLAY);
    }

    public function setVideoAutoplay(bool $autoplay): BannerInterface
    {
        return $this->setData(self::VIDEO_AUTOPLAY, $autoplay ? 1 : 0);
    }

    public function getVideoMuted(): bool
    {
        return (bool)$this->getData(self::VIDEO_MUTED);
    }

    public function setVideoMuted(bool $muted): BannerInterface
    {
        return $this->setData(self::VIDEO_MUTED, $muted ? 1 : 0);
    }

    public function getContent(): ?string
    {
        $value = $this->getData(self::CONTENT);
        return $value === null ? null : (string)$value;
    }

    public function setContent(?string $content): BannerInterface
    {
        return $this->setData(self::CONTENT, $content);
    }

    public function getProductId(): ?int
    {
        $value = $this->getData(self::PRODUCT_ID);
        return $value === null || $value === '' ? null : (int)$value;
    }

    public function setProductId(?int $productId): BannerInterface
    {
        return $this->setData(self::PRODUCT_ID, $productId);
    }

    public function getCountdownTo(): ?string
    {
        $value = $this->getData(self::COUNTDOWN_TO);
        return $value === null ? null : (string)$value;
    }

    public function setCountdownTo(?string $countdownTo): BannerInterface
    {
        return $this->setData(self::COUNTDOWN_TO, $countdownTo);
    }

    public function getCountdownHideExpired(): bool
    {
        return (bool)$this->getData(self::COUNTDOWN_HIDE_EXPIRED);
    }

    public function setCountdownHideExpired(bool $hide): BannerInterface
    {
        return $this->setData(self::COUNTDOWN_HIDE_EXPIRED, $hide ? 1 : 0);
    }

    public function getFromDate(): ?string
    {
        $value = $this->getData(self::FROM_DATE);
        return $value === null ? null : (string)$value;
    }

    public function setFromDate(?string $fromDate): BannerInterface
    {
        return $this->setData(self::FROM_DATE, $fromDate);
    }

    public function getToDate(): ?string
    {
        $value = $this->getData(self::TO_DATE);
        return $value === null ? null : (string)$value;
    }

    public function setToDate(?string $toDate): BannerInterface
    {
        return $this->setData(self::TO_DATE, $toDate);
    }

    public function getStoreIds(): array
    {
        return $this->explodeIds($this->getData(self::STORE_IDS));
    }

    public function setStoreIds(array $storeIds): BannerInterface
    {
        return $this->setData(self::STORE_IDS, implode(',', $storeIds));
    }

    public function getCustomerGroupIds(): array
    {
        return $this->explodeIds($this->getData(self::CUSTOMER_GROUP_IDS));
    }

    public function setCustomerGroupIds(array $groupIds): BannerInterface
    {
        return $this->setData(self::CUSTOMER_GROUP_IDS, implode(',', $groupIds));
    }

    public function getConditionsSerialized(): ?string
    {
        $value = $this->getData(self::CONDITIONS_SERIALIZED);
        return $value === null ? null : (string)$value;
    }

    public function setConditionsSerialized(?string $conditions): BannerInterface
    {
        return $this->setData(self::CONDITIONS_SERIALIZED, $conditions);
    }

    public function getCreatedAt(): ?string
    {
        $value = $this->getData(self::CREATED_AT);
        return $value === null ? null : (string)$value;
    }

    public function setCreatedAt(string $createdAt): BannerInterface
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    public function getUpdatedAt(): ?string
    {
        $value = $this->getData(self::UPDATED_AT);
        return $value === null ? null : (string)$value;
    }

    public function setUpdatedAt(string $updatedAt): BannerInterface
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }

    /**
     * Convert a stored comma-separated id list into an int array.
     *
     * @param mixed $value
     * @return int[]
     */
    private function explodeIds($value): array
    {
        if ($value === null || $value === '') {
            return [];
        }
        if (is_array($value)) {
            return array_map('intval', $value);
        }
        return array_values(array_filter(array_map('intval', explode(',', (string)$value)), static function ($id) {
            return $id >= 0;
        }));
    }
}
