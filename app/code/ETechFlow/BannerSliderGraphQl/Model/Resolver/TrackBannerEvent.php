<?php
declare(strict_types=1);

namespace ETechFlow\BannerSliderGraphQl\Model\Resolver;

use ETechFlow\BannerSlider\Model\Stat\StatRecorder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Records a headless banner event (impression / click / add_to_cart) into the
 * aggregated stat table. Order/revenue attribution stays server-side via the
 * order observer, so `order` is rejected here just like the REST beacon.
 */
class TrackBannerEvent implements ResolverInterface
{
    private const XML_ENABLED = 'etechflow_bannerslider/general/enabled';
    private const XML_TRACKING = 'etechflow_bannerslider/performance/async_tracking';

    public function __construct(
        private readonly StatRecorder $statRecorder,
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * @inheritDoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, ?array $value = null, ?array $args = null)
    {
        $input = $args['input'] ?? [];
        $bannerId = (int)($input['banner_id'] ?? 0);
        $sliderId = (int)($input['slider_id'] ?? 0);
        $eventType = (string)($input['event_type'] ?? '');

        if ($bannerId <= 0 || $sliderId <= 0) {
            throw new GraphQlInputException(__('"banner_id" and "slider_id" are required.'));
        }
        if (!in_array($eventType, StatRecorder::CLIENT_EVENTS, true)) {
            throw new GraphQlInputException(
                __('Unsupported event_type. Use one of: %1', implode(', ', StatRecorder::CLIENT_EVENTS))
            );
        }

        $store = $context->getExtensionAttributes()->getStore();
        $storeId = $store instanceof StoreInterface ? (int)$store->getId() : 0;

        $enabled = $this->scopeConfig->isSetFlag(self::XML_ENABLED, ScopeInterface::SCOPE_STORE, $storeId)
            && $this->scopeConfig->isSetFlag(self::XML_TRACKING, ScopeInterface::SCOPE_STORE, $storeId);
        if (!$enabled) {
            return ['success' => false];
        }

        $saved = $this->statRecorder->record(
            [[
                'banner_id' => $bannerId,
                'slider_id' => $sliderId,
                'variant' => (string)($input['variant'] ?? 'default'),
                'event_type' => $eventType,
            ]],
            $storeId,
            StatRecorder::CLIENT_EVENTS
        );

        return ['success' => $saved > 0];
    }
}
