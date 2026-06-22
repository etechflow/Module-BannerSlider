<?php
declare(strict_types=1);

namespace ETechFlow\BannerSlider\Model;

use ETechFlow\BannerSlider\Api\Data\SliderInterface;
use ETechFlow\BannerSlider\Model\ResourceModel\Slider as SliderResource;
use Magento\Framework\Model\AbstractModel;

class Slider extends AbstractModel implements SliderInterface
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'etechflow_bannerslider_slider';

    protected function _construct(): void
    {
        $this->_init(SliderResource::class);
    }

    public function getTitle(): ?string
    {
        $value = $this->getData(self::TITLE);
        return $value === null ? null : (string)$value;
    }

    public function setTitle(string $title): SliderInterface
    {
        return $this->setData(self::TITLE, $title);
    }

    public function getStatus(): int
    {
        return (int)$this->getData(self::STATUS);
    }

    public function setStatus(int $status): SliderInterface
    {
        return $this->setData(self::STATUS, $status);
    }

    public function getStoreIds(): array
    {
        return $this->explodeIds($this->getData(self::STORE_IDS));
    }

    public function setStoreIds(array $storeIds): SliderInterface
    {
        return $this->setData(self::STORE_IDS, implode(',', $storeIds));
    }

    public function getCustomerGroupIds(): array
    {
        return $this->explodeIds($this->getData(self::CUSTOMER_GROUP_IDS));
    }

    public function setCustomerGroupIds(array $groupIds): SliderInterface
    {
        return $this->setData(self::CUSTOMER_GROUP_IDS, implode(',', $groupIds));
    }

    public function getAutoplay(): bool
    {
        return (bool)$this->getData(self::AUTOPLAY);
    }

    public function setAutoplay(bool $autoplay): SliderInterface
    {
        return $this->setData(self::AUTOPLAY, $autoplay ? 1 : 0);
    }

    public function getAutoplaySpeed(): int
    {
        return (int)$this->getData(self::AUTOPLAY_SPEED);
    }

    public function setAutoplaySpeed(int $speed): SliderInterface
    {
        return $this->setData(self::AUTOPLAY_SPEED, $speed);
    }

    public function getAnimationSpeed(): int
    {
        return (int)$this->getData(self::ANIMATION_SPEED);
    }

    public function setAnimationSpeed(int $speed): SliderInterface
    {
        return $this->setData(self::ANIMATION_SPEED, $speed);
    }

    public function getAnimationEffect(): string
    {
        return (string)$this->getData(self::ANIMATION_EFFECT);
    }

    public function setAnimationEffect(string $effect): SliderInterface
    {
        return $this->setData(self::ANIMATION_EFFECT, $effect);
    }

    public function getShowArrows(): bool
    {
        return (bool)$this->getData(self::SHOW_ARROWS);
    }

    public function setShowArrows(bool $show): SliderInterface
    {
        return $this->setData(self::SHOW_ARROWS, $show ? 1 : 0);
    }

    public function getShowBullets(): bool
    {
        return (bool)$this->getData(self::SHOW_BULLETS);
    }

    public function setShowBullets(bool $show): SliderInterface
    {
        return $this->setData(self::SHOW_BULLETS, $show ? 1 : 0);
    }

    public function getIsLoop(): bool
    {
        return (bool)$this->getData(self::IS_LOOP);
    }

    public function setIsLoop(bool $loop): SliderInterface
    {
        return $this->setData(self::IS_LOOP, $loop ? 1 : 0);
    }

    public function getPauseOnHover(): bool
    {
        return (bool)$this->getData(self::PAUSE_ON_HOVER);
    }

    public function setPauseOnHover(bool $pause): SliderInterface
    {
        return $this->setData(self::PAUSE_ON_HOVER, $pause ? 1 : 0);
    }

    public function getLazyLoad(): bool
    {
        return (bool)$this->getData(self::LAZY_LOAD);
    }

    public function setLazyLoad(bool $lazy): SliderInterface
    {
        return $this->setData(self::LAZY_LOAD, $lazy ? 1 : 0);
    }

    public function getConditionsSerialized(): ?string
    {
        $value = $this->getData(self::CONDITIONS_SERIALIZED);
        return $value === null ? null : (string)$value;
    }

    public function setConditionsSerialized(?string $conditions): SliderInterface
    {
        return $this->setData(self::CONDITIONS_SERIALIZED, $conditions);
    }

    public function getIsAbTest(): bool
    {
        return (bool)$this->getData(self::IS_AB_TEST);
    }

    public function setIsAbTest(bool $isAbTest): SliderInterface
    {
        return $this->setData(self::IS_AB_TEST, $isAbTest ? 1 : 0);
    }

    public function getAbGoal(): string
    {
        return (string)$this->getData(self::AB_GOAL);
    }

    public function setAbGoal(string $goal): SliderInterface
    {
        return $this->setData(self::AB_GOAL, $goal);
    }

    public function getAbWinner(): ?string
    {
        $value = $this->getData(self::AB_WINNER);
        return $value === null || $value === '' ? null : (string)$value;
    }

    public function setAbWinner(?string $variant): SliderInterface
    {
        return $this->setData(self::AB_WINNER, $variant);
    }

    public function getAbConcludedAt(): ?string
    {
        $value = $this->getData(self::AB_CONCLUDED_AT);
        return $value === null ? null : (string)$value;
    }

    public function setAbConcludedAt(?string $concludedAt): SliderInterface
    {
        return $this->setData(self::AB_CONCLUDED_AT, $concludedAt);
    }

    public function getPriority(): int
    {
        return (int)$this->getData(self::PRIORITY);
    }

    public function setPriority(int $priority): SliderInterface
    {
        return $this->setData(self::PRIORITY, $priority);
    }

    public function getCreatedAt(): ?string
    {
        $value = $this->getData(self::CREATED_AT);
        return $value === null ? null : (string)$value;
    }

    public function setCreatedAt(string $createdAt): SliderInterface
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    public function getUpdatedAt(): ?string
    {
        $value = $this->getData(self::UPDATED_AT);
        return $value === null ? null : (string)$value;
    }

    public function setUpdatedAt(string $updatedAt): SliderInterface
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
