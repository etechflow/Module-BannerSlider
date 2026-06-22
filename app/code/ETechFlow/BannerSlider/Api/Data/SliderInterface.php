<?php
declare(strict_types=1);

namespace ETechFlow\BannerSlider\Api\Data;

/**
 * Slider data interface.
 *
 * @api
 */
interface SliderInterface
{
    public const SLIDER_ID = 'slider_id';
    public const TITLE = 'title';
    public const STATUS = 'status';
    public const STORE_IDS = 'store_ids';
    public const CUSTOMER_GROUP_IDS = 'customer_group_ids';
    public const AUTOPLAY = 'autoplay';
    public const AUTOPLAY_SPEED = 'autoplay_speed';
    public const ANIMATION_SPEED = 'animation_speed';
    public const ANIMATION_EFFECT = 'animation_effect';
    public const SHOW_ARROWS = 'show_arrows';
    public const SHOW_BULLETS = 'show_bullets';
    public const IS_LOOP = 'is_loop';
    public const PAUSE_ON_HOVER = 'pause_on_hover';
    public const LAZY_LOAD = 'lazy_load';
    public const CONDITIONS_SERIALIZED = 'conditions_serialized';
    public const IS_AB_TEST = 'is_ab_test';
    public const AB_GOAL = 'ab_goal';
    public const AB_WINNER = 'ab_winner';
    public const AB_CONCLUDED_AT = 'ab_concluded_at';
    public const PRIORITY = 'priority';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

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
    public function getTitle(): ?string;

    /**
     * @param string $title
     * @return $this
     */
    public function setTitle(string $title): self;

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
     * @return bool
     */
    public function getAutoplay(): bool;

    /**
     * @param bool $autoplay
     * @return $this
     */
    public function setAutoplay(bool $autoplay): self;

    /**
     * @return int
     */
    public function getAutoplaySpeed(): int;

    /**
     * @param int $speed
     * @return $this
     */
    public function setAutoplaySpeed(int $speed): self;

    /**
     * @return int
     */
    public function getAnimationSpeed(): int;

    /**
     * @param int $speed
     * @return $this
     */
    public function setAnimationSpeed(int $speed): self;

    /**
     * @return string
     */
    public function getAnimationEffect(): string;

    /**
     * @param string $effect
     * @return $this
     */
    public function setAnimationEffect(string $effect): self;

    /**
     * @return bool
     */
    public function getShowArrows(): bool;

    /**
     * @param bool $show
     * @return $this
     */
    public function setShowArrows(bool $show): self;

    /**
     * @return bool
     */
    public function getShowBullets(): bool;

    /**
     * @param bool $show
     * @return $this
     */
    public function setShowBullets(bool $show): self;

    /**
     * @return bool
     */
    public function getIsLoop(): bool;

    /**
     * @param bool $loop
     * @return $this
     */
    public function setIsLoop(bool $loop): self;

    /**
     * @return bool
     */
    public function getPauseOnHover(): bool;

    /**
     * @param bool $pause
     * @return $this
     */
    public function setPauseOnHover(bool $pause): self;

    /**
     * @return bool
     */
    public function getLazyLoad(): bool;

    /**
     * @param bool $lazy
     * @return $this
     */
    public function setLazyLoad(bool $lazy): self;

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
     * @return bool
     */
    public function getIsAbTest(): bool;

    /**
     * @param bool $isAbTest
     * @return $this
     */
    public function setIsAbTest(bool $isAbTest): self;

    /**
     * @return string
     */
    public function getAbGoal(): string;

    /**
     * @param string $goal
     * @return $this
     */
    public function setAbGoal(string $goal): self;

    /**
     * @return string|null
     */
    public function getAbWinner(): ?string;

    /**
     * @param string|null $variant
     * @return $this
     */
    public function setAbWinner(?string $variant): self;

    /**
     * @return string|null
     */
    public function getAbConcludedAt(): ?string;

    /**
     * @param string|null $concludedAt
     * @return $this
     */
    public function setAbConcludedAt(?string $concludedAt): self;

    /**
     * @return int
     */
    public function getPriority(): int;

    /**
     * @param int $priority
     * @return $this
     */
    public function setPriority(int $priority): self;

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
