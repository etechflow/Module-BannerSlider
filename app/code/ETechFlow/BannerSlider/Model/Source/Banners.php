<?php
declare(strict_types=1);

namespace ETechFlow\BannerSlider\Model\Source;

use ETechFlow\BannerSlider\Model\ResourceModel\Banner\CollectionFactory;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Option source listing every banner (id => "Name (#id)") for the slider assignment grid.
 */
class Banners implements OptionSourceInterface
{
    private ?array $options = null;

    public function __construct(
        private readonly CollectionFactory $collectionFactory
    ) {
    }

    public function toOptionArray(): array
    {
        if ($this->options !== null) {
            return $this->options;
        }

        $this->options = [];
        $collection = $this->collectionFactory->create();
        $collection->addFieldToSelect(['banner_id', 'name', 'type']);
        $collection->setOrder('banner_id', 'ASC');

        foreach ($collection as $banner) {
            $this->options[] = [
                'value' => (int)$banner->getId(),
                'label' => sprintf('%s [%s] (#%d)', $banner->getName(), $banner->getType(), $banner->getId()),
            ];
        }
        return $this->options;
    }
}
