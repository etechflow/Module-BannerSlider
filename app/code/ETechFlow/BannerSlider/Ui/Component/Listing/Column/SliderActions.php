<?php
declare(strict_types=1);

namespace ETechFlow\BannerSlider\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class SliderActions extends Column
{
    private const EDIT_URL = 'etechflow_bannerslider/slider/edit';
    private const DELETE_URL = 'etechflow_bannerslider/slider/delete';

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        private readonly UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource): array
    {
        if (empty($dataSource['data']['items'])) {
            return $dataSource;
        }

        $name = $this->getData('name');
        foreach ($dataSource['data']['items'] as &$item) {
            if (!isset($item['slider_id'])) {
                continue;
            }
            $id = $item['slider_id'];
            $item[$name]['edit'] = [
                'href' => $this->urlBuilder->getUrl(self::EDIT_URL, ['slider_id' => $id]),
                'label' => __('Edit'),
            ];
            $item[$name]['delete'] = [
                'href' => $this->urlBuilder->getUrl(self::DELETE_URL, ['slider_id' => $id]),
                'label' => __('Delete'),
                'confirm' => [
                    'title' => __('Delete "%1"', $item['title'] ?? $id),
                    'message' => __('Are you sure you want to delete this slider?'),
                ],
                'post' => true,
            ];
        }

        return $dataSource;
    }
}
