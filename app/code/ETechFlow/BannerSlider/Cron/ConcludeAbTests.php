<?php
declare(strict_types=1);

namespace ETechFlow\BannerSlider\Cron;

use ETechFlow\BannerSlider\Api\SliderRepositoryInterface;
use ETechFlow\BannerSlider\Model\Ab\AbTestResolver;
use ETechFlow\BannerSlider\Model\ResourceModel\Slider\CollectionFactory as SliderCollectionFactory;
use ETechFlow\BannerSlider\Model\Slider;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Psr\Log\LoggerInterface;

/**
 * Daily auto-conclusion of running A/B tests.
 *
 * For each slider with an active test, once every variant has gathered enough
 * impressions, the winning variant (by the slider's goal) is recorded and the
 * split is switched off — after which the storefront serves only the winner
 * (filtered server-side, so the page stays cacheable).
 */
class ConcludeAbTests
{
    private const XML_AUTO_CONCLUDE = 'etechflow_bannerslider/analytics/ab_auto_conclude';
    private const XML_MIN_IMPRESSIONS = 'etechflow_bannerslider/analytics/ab_min_impressions';
    private const HISTORY_START = '2000-01-01';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly SliderCollectionFactory $sliderCollectionFactory,
        private readonly SliderRepositoryInterface $sliderRepository,
        private readonly AbTestResolver $abTestResolver,
        private readonly TimezoneInterface $timezone,
        private readonly LoggerInterface $logger
    ) {
    }

    public function execute(): void
    {
        if (!$this->scopeConfig->isSetFlag(self::XML_AUTO_CONCLUDE)) {
            return;
        }

        $minImpressions = (int)$this->scopeConfig->getValue(self::XML_MIN_IMPRESSIONS);
        if ($minImpressions <= 0) {
            $minImpressions = 1000;
        }
        $today = $this->timezone->date()->format('Y-m-d');

        $collection = $this->sliderCollectionFactory->create();
        $collection->addFieldToFilter(Slider::IS_AB_TEST, 1);

        /** @var Slider $slider */
        foreach ($collection as $slider) {
            try {
                $winner = $this->abTestResolver->pickWinner(
                    (int)$slider->getId(),
                    $slider->getAbGoal(),
                    self::HISTORY_START,
                    $today,
                    $minImpressions
                );
                if ($winner === null) {
                    continue;
                }

                $slider->setAbWinner($winner);
                $slider->setAbConcludedAt($this->timezone->date()->format('Y-m-d H:i:s'));
                $slider->setIsAbTest(false);
                $this->sliderRepository->save($slider);

                $this->logger->info(sprintf(
                    'ETechFlow BannerSlider: A/B test for slider #%d concluded — winner "%s" (goal %s).',
                    (int)$slider->getId(),
                    $winner,
                    $slider->getAbGoal()
                ));
            } catch (\Throwable $e) {
                $this->logger->warning(
                    'ETechFlow BannerSlider: failed concluding A/B for slider #'
                    . $slider->getId() . ': ' . $e->getMessage()
                );
            }
        }
    }
}
