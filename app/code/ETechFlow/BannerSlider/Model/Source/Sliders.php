<?php
declare(strict_types=1);

namespace ETechFlow\BannerSlider\Model\Source;

use ETechFlow\BannerSlider\Model\ResourceModel\Slider\CollectionFactory;
use Magento\Framework\Option\ArrayInterface;

/**
 * Slider option source for the widget configuration select.
 */
class Sliders implements ArrayInterface
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

        $this->options = [['value' => '', 'label' => __('-- Please Select a Slider --')]];
        $collection = $this->collectionFactory->create();
        $collection->addFieldToSelect(['slider_id', 'title']);
        $collection->setOrder('slider_id', 'ASC');

        foreach ($collection as $slider) {
            $this->options[] = [
                'value' => (int)$slider->getId(),
                'label' => sprintf('%s (#%d)', $slider->getTitle(), $slider->getId()),
            ];
        }
        return $this->options;
    }
}
