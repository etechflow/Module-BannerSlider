<?php
declare(strict_types=1);

namespace ETechFlow\BannerSlider\Block\Adminhtml\Report;

use ETechFlow\BannerSlider\Api\Data\SliderInterface;
use ETechFlow\BannerSlider\Model\Ab\AbTestResolver;
use ETechFlow\BannerSlider\Model\ResourceModel\Slider\CollectionFactory as SliderCollectionFactory;
use ETechFlow\BannerSlider\Model\Stat\StatsProvider;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

/**
 * Backing block for the statistics dashboard.
 *
 * NB: the data accessor is getReportData(), not getData() — a block method
 * named getData() shadows the framework getter and breaks rendering.
 */
class Dashboard extends Template
{
    public function __construct(
        Context $context,
        private readonly StatsProvider $statsProvider,
        private readonly TimezoneInterface $timezone,
        private readonly PriceCurrencyInterface $priceCurrency,
        private readonly SliderCollectionFactory $sliderCollectionFactory,
        private readonly AbTestResolver $abTestResolver,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getFromDate(): string
    {
        return $this->validDate((string)$this->getRequest()->getParam('from'))
            ?? $this->timezone->date()->modify('-29 days')->format('Y-m-d');
    }

    public function getToDate(): string
    {
        return $this->validDate((string)$this->getRequest()->getParam('to'))
            ?? $this->timezone->date()->format('Y-m-d');
    }

    /**
     * @return array<int, array>
     */
    public function getReportData(): array
    {
        return $this->statsProvider->getPerBanner($this->getFromDate(), $this->getToDate());
    }

    /**
     * @return array<string, mixed>
     */
    public function getKpis(): array
    {
        return $this->statsProvider->getKpis($this->getFromDate(), $this->getToDate());
    }

    /**
     * Per-slider A/B reports for sliders that are running or have concluded a
     * test, with each variant's stats and the leading / winning variant.
     *
     * @return array<int, array>
     */
    public function getAbReports(): array
    {
        $collection = $this->sliderCollectionFactory->create();
        $collection->addFieldToFilter(
            [SliderInterface::IS_AB_TEST, SliderInterface::AB_WINNER],
            [['eq' => 1], ['notnull' => true]]
        );

        $from = $this->getFromDate();
        $to = $this->getToDate();
        $reports = [];
        foreach ($collection as $slider) {
            $report = $this->abTestResolver->getReport(
                (int)$slider->getId(),
                $slider->getAbGoal(),
                $from,
                $to
            );
            if (!$report['variants']) {
                continue;
            }
            $reports[] = [
                'slider_id' => (int)$slider->getId(),
                'title' => (string)$slider->getTitle(),
                'goal' => $slider->getAbGoal(),
                'running' => $slider->getIsAbTest(),
                'winner' => $slider->getAbWinner(),
                'leader' => $report['leader'],
                'variants' => $report['variants'],
            ];
        }
        return $reports;
    }

    public function getFormAction(): string
    {
        return $this->getUrl('*/*/dashboard');
    }

    public function getExportUrl(): string
    {
        return $this->getUrl('*/*/exportCsv', ['from' => $this->getFromDate(), 'to' => $this->getToDate()]);
    }

    public function formatPrice(float $value): string
    {
        return $this->priceCurrency->format($value, false);
    }

    private function validDate(string $value): ?string
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : null;
    }
}
