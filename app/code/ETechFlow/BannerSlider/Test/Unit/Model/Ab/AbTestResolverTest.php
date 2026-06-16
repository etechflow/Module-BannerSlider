<?php
declare(strict_types=1);

namespace ETechFlow\BannerSlider\Test\Unit\Model\Ab;

use ETechFlow\BannerSlider\Model\Ab\AbTestResolver;
use ETechFlow\BannerSlider\Model\Stat\StatsProvider;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

class AbTestResolverTest extends TestCase
{
    private StatsProvider&Stub $statsProvider;

    private AbTestResolver $resolver;

    protected function setUp(): void
    {
        // A stub (not a mock): these tests only need canned return values,
        // not interaction verification.
        $this->statsProvider = $this->createStub(StatsProvider::class);
        $this->resolver = new AbTestResolver($this->statsProvider);
    }

    /**
     * @param array<int,array> $variants
     */
    private function stub(array $variants): void
    {
        $this->statsProvider->method('getPerVariant')->willReturn($variants);
    }

    public function testPicksHigherCtrVariant(): void
    {
        $this->stub([
            ['variant' => 'A', 'impressions' => 1000, 'clicks' => 40, 'add_to_cart' => 0, 'orders' => 0, 'revenue' => 0.0, 'ctr' => 4.0, 'atc_rate' => 0.0, 'rev_per_impression' => 0.0],
            ['variant' => 'B', 'impressions' => 1000, 'clicks' => 75, 'add_to_cart' => 0, 'orders' => 0, 'revenue' => 0.0, 'ctr' => 7.5, 'atc_rate' => 0.0, 'rev_per_impression' => 0.0],
        ]);
        $this->assertSame('B', $this->resolver->pickWinner(1, 'ctr', '2000-01-01', '2030-01-01', 500));
    }

    public function testRevenueGoalUsesPerImpressionRate(): void
    {
        $this->stub([
            ['variant' => 'A', 'impressions' => 1000, 'clicks' => 0, 'add_to_cart' => 0, 'orders' => 5, 'revenue' => 500.0, 'ctr' => 0.0, 'atc_rate' => 0.0, 'rev_per_impression' => 0.5],
            ['variant' => 'B', 'impressions' => 1000, 'clicks' => 0, 'add_to_cart' => 0, 'orders' => 3, 'revenue' => 900.0, 'ctr' => 0.0, 'atc_rate' => 0.0, 'rev_per_impression' => 0.9],
        ]);
        $this->assertSame('B', $this->resolver->pickWinner(1, 'revenue', '2000-01-01', '2030-01-01', 500));
    }

    public function testNoWinnerWhenFewerThanTwoQualify(): void
    {
        $this->stub([
            ['variant' => 'A', 'impressions' => 1000, 'clicks' => 40, 'add_to_cart' => 0, 'orders' => 0, 'revenue' => 0.0, 'ctr' => 4.0, 'atc_rate' => 0.0, 'rev_per_impression' => 0.0],
            ['variant' => 'B', 'impressions' => 100, 'clicks' => 20, 'add_to_cart' => 0, 'orders' => 0, 'revenue' => 0.0, 'ctr' => 20.0, 'atc_rate' => 0.0, 'rev_per_impression' => 0.0],
        ]);
        // B has the higher CTR but only 100 impressions (< 500), so the test is not decisive.
        $this->assertNull($this->resolver->pickWinner(1, 'ctr', '2000-01-01', '2030-01-01', 500));
    }

    public function testNoWinnerWhenLeadingMetricIsZero(): void
    {
        $this->stub([
            ['variant' => 'A', 'impressions' => 1000, 'clicks' => 0, 'add_to_cart' => 0, 'orders' => 0, 'revenue' => 0.0, 'ctr' => 0.0, 'atc_rate' => 0.0, 'rev_per_impression' => 0.0],
            ['variant' => 'B', 'impressions' => 1000, 'clicks' => 0, 'add_to_cart' => 0, 'orders' => 0, 'revenue' => 0.0, 'ctr' => 0.0, 'atc_rate' => 0.0, 'rev_per_impression' => 0.0],
        ]);
        $this->assertNull($this->resolver->pickWinner(1, 'ctr', '2000-01-01', '2030-01-01', 500));
    }

    public function testGetReportExposesLeaderAndMetric(): void
    {
        $this->stub([
            ['variant' => 'A', 'impressions' => 10, 'clicks' => 1, 'add_to_cart' => 0, 'orders' => 0, 'revenue' => 0.0, 'ctr' => 10.0, 'atc_rate' => 0.0, 'rev_per_impression' => 0.0],
            ['variant' => 'B', 'impressions' => 10, 'clicks' => 3, 'add_to_cart' => 0, 'orders' => 0, 'revenue' => 0.0, 'ctr' => 30.0, 'atc_rate' => 0.0, 'rev_per_impression' => 0.0],
        ]);
        $report = $this->resolver->getReport(1, 'ctr', '2000-01-01', '2030-01-01');
        $this->assertSame('ctr', $report['metric']);
        $this->assertSame('B', $report['leader']);
        $this->assertCount(2, $report['variants']);
    }
}
