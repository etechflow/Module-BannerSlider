<?php
declare(strict_types=1);

namespace ETechFlow\BannerSlider\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Store\Model\System\Store as SystemStore;

class StoreView implements OptionSourceInterface
{
    public function __construct(
        private readonly SystemStore $systemStore
    ) {
    }

    public function toOptionArray(): array
    {
        // Flatten the website/group/store-view tree into a simple option list,
        // prefixed with an "All Store Views" choice (value 0).
        $options = [['value' => '0', 'label' => __('All Store Views')]];
        foreach ($this->systemStore->getStoreValuesForForm(false, true) as $option) {
            if (!isset($option['value']) || is_array($option['value'])) {
                continue;
            }
            $options[] = $option;
        }
        return $options;
    }
}
