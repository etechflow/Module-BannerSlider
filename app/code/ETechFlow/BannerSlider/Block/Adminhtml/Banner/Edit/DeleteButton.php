<?php
declare(strict_types=1);

namespace ETechFlow\BannerSlider\Block\Adminhtml\Banner\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class DeleteButton extends GenericButton implements ButtonProviderInterface
{
    public function getButtonData(): array
    {
        if (!$this->getBannerId()) {
            return [];
        }
        return [
            'label' => __('Delete Banner'),
            'class' => 'delete',
            'on_click' => sprintf(
                "deleteConfirm('%s', '%s')",
                __('Are you sure you want to delete this banner?'),
                $this->getUrl('*/*/delete', ['banner_id' => $this->getBannerId()])
            ),
            'sort_order' => 20,
        ];
    }
}
