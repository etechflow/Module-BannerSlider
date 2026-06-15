<?php
declare(strict_types=1);

namespace ETechFlow\BannerSlider\Api\Data;

/**
 * Banner data interface.
 *
 * @api
 */
interface BannerInterface
{
    public const BANNER_ID = 'banner_id';
    public const NAME = 'name';
    public const STATUS = 'status';
    public const TYPE = 'type';
    public const IMAGE = 'image';
    public const IMAGE_MOBILE = 'image_mobile';
    public const ALT_TEXT = 'alt_text';
    public const URL = 'url';
    public const OPEN_NEW_TAB = 'open_new_tab';
    public const VIDEO_TYPE = 'video_type';
    public const VIDEO_URL = 'video_url';
    public const VIDEO_AUTOPLAY = 'video_autoplay';
    public const VIDEO_MUTED = 'video_muted';
    public const CONTENT = 'content';
    public const PRODUCT_ID = 'product_id';
    public const COUNTDOWN_TO = 'countdown_to';
    public const COUNTDOWN_HIDE_EXPIRED = 'countdown_hide_expired';
    public const FROM_DATE = 'from_date';
    public const TO_DATE = 'to_date';
    public const STORE_IDS = 'store_ids';
    public const CUSTOMER_GROUP_IDS = 'customer_group_ids';
    public const CONDITIONS_SERIALIZED = 'conditions_serialized';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    /** Banner type values */
    public const TYPE_IMAGE = 'image';
    public const TYPE_VIDEO = 'video';
    public const TYPE_HTML = 'html';
    public const TYPE_PRODUCT = 'product';
    public const TYPE_COUNTDOWN = 'countdown';

    /**
     * @return int|null
     */
    public function getId();

    /**
     * @param int $id
     * @return $this
     */
    public function setId($id);

    /**
     * @return string|null
     */
    public function getName(): ?string;

    /**
     * @param string $name
     * @return $this
     */
    public function setName(string $name): self;

    /**
     * @return int
     */
    public function getStatus(): int;

    /**
     * @param int $status
     * @return $this
     */
    public function setStatus(int $status): self;

    /**
     * @return string
     */
    public function getType(): string;

    /**
     * @param string $type
     * @return $this
     */
    public function setType(string $type): self;

    /**
     * @return string|null
     */
    public function getImage(): ?string;

    /**
     * @param string|null $image
     * @return $this
     */
    public function setImage(?string $image): self;

    /**
     * @return string|null
     */
    public function getImageMobile(): ?string;

    /**
     * @param string|null $image
     * @return $this
     */
    public function setImageMobile(?string $image): self;

    /**
     * @return string|null
     */
    public function getAltText(): ?string;

    /**
     * @param string|null $altText
     * @return $this
     */
    public function setAltText(?string $altText): self;

    /**
     * @return string|null
     */
    public function getUrl(): ?string;

    /**
     * @param string|null $url
     * @return $this
     */
    public function setUrl(?string $url): self;

    /**
     * @return bool
     */
    public function getOpenNewTab(): bool;

    /**
     * @param bool $openNewTab
     * @return $this
     */
    public function setOpenNewTab(bool $openNewTab): self;

    /**
     * @return string|null
     */
    public function getVideoType(): ?string;

    /**
     * @param string|null $videoType
     * @return $this
     */
    public function setVideoType(?string $videoType): self;

    /**
     * @return string|null
     */
    public function getVideoUrl(): ?string;

    /**
     * @param string|null $videoUrl
     * @return $this
     */
    public function setVideoUrl(?string $videoUrl): self;

    /**
     * @return bool
     */
    public function getVideoAutoplay(): bool;

    /**
     * @param bool $autoplay
     * @return $this
     */
    public function setVideoAutoplay(bool $autoplay): self;

    /**
     * @return bool
     */
    public function getVideoMuted(): bool;

    /**
     * @param bool $muted
     * @return $this
     */
    public function setVideoMuted(bool $muted): self;

    /**
     * @return string|null
     */
    public function getContent(): ?string;

    /**
     * @param string|null $content
     * @return $this
     */
    public function setContent(?string $content): self;

    /**
     * @return int|null
     */
    public function getProductId(): ?int;

    /**
     * @param int|null $productId
     * @return $this
     */
    public function setProductId(?int $productId): self;

    /**
     * @return string|null
     */
    public function getCountdownTo(): ?string;

    /**
     * @param string|null $countdownTo
     * @return $this
     */
    public function setCountdownTo(?string $countdownTo): self;

    /**
     * @return bool
     */
    public function getCountdownHideExpired(): bool;

    /**
     * @param bool $hide
     * @return $this
     */
    public function setCountdownHideExpired(bool $hide): self;

    /**
     * @return string|null
     */
    public function getFromDate(): ?string;

    /**
     * @param string|null $fromDate
     * @return $this
     */
    public function setFromDate(?string $fromDate): self;

    /**
     * @return string|null
     */
    public function getToDate(): ?string;

    /**
     * @param string|null $toDate
     * @return $this
     */
    public function setToDate(?string $toDate): self;

    /**
     * @return int[]
     */
    public function getStoreIds(): array;

    /**
     * @param int[] $storeIds
     * @return $this
     */
    public function setStoreIds(array $storeIds): self;

    /**
     * @return int[]
     */
    public function getCustomerGroupIds(): array;

    /**
     * @param int[] $groupIds
     * @return $this
     */
    public function setCustomerGroupIds(array $groupIds): self;

    /**
     * @return string|null
     */
    public function getConditionsSerialized(): ?string;

    /**
     * @param string|null $conditions
     * @return $this
     */
    public function setConditionsSerialized(?string $conditions): self;

    /**
     * @return string|null
     */
    public function getCreatedAt(): ?string;

    /**
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt(string $createdAt): self;

    /**
     * @return string|null
     */
    public function getUpdatedAt(): ?string;

    /**
     * @param string $updatedAt
     * @return $this
     */
    public function setUpdatedAt(string $updatedAt): self;
}
