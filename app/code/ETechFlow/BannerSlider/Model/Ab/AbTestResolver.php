<?php
declare(strict_types=1);

namespace ETechFlow\BannerSlider\Model\Ab;

use ETechFlow\BannerSlider\Model\Stat\StatsProvider;

/**
 * Turns per-variant statistics into an A/B leader (for reporting) or a
 * confident winner (for auto-conclusion), according to the slider's goal.
 */
class AbTestResolver
{
    /** Map a slider goal to the rate column compared between variants. */
    private const GOAL_METRIC = [
        'ctr' => 'ctr',
        'add_to_cart' => 'atc_rate',
        'revenue' => 'rev_per_impression',
    ];

    public function __construct(
        private readonly StatsProvider $statsProvider
    ) {
    }

    /**
     * Per-variant rows plus the currently leading variant (no sample gate —
     * for display only).
     *
     * @param int $sliderId
     * @param string $goal
     * @param string $from Y-m-d
     * @param string $to Y-m-d
     * @return array{variants:array, metric:string, leader:?string}
     */
    public function getReport(int $sliderId, string $goal, string $from, string $to): array
    {
        $variants = $this->statsProvider->getPerVariant($sliderId, $from, $to);
        $metric = $this->metricFor($goal);
        $leader = $this->leadingVariant($variants, $metric);

        return ['variants' => $variants, 'metric' => $metric, 'leader' => $leader];
    }

    /**
     * A statistically-eligible winner, or null when the test is not yet
     * decisive (fewer than two variants meeting the impression threshold, or a
     * zero leading metric).
     *
     * @param int $sliderId
     * @param string $goal
     * @param string $from Y-m-d
     * @param string $to Y-m-d
     * @param int $minImpressions
     * @return string|null
     */
    public function pickWinner(int $sliderId, string $goal, string $from, string $to, int $minImpressions): ?string
    {
        $variants = $this->statsProvider->getPerVariant($sliderId, $from, $to);
        $qualified = array_values(array_filter(
            $variants,
            static fn ($v) => $v['impressions'] >= $minImpressions
        ));
        if (count($qualified) < 2) {
            return null;
        }

        $metric = $this->metricFor($goal);
        $winner = $this->leadingVariant($qualified, $metric);
        $best = $winner !== null ? $this->scoreOf($qualified, $winner, $metric) : 0.0;

        return $best > 0 ? $winner : null;
    }

    private function metricFor(string $goal): string
    {
        return self::GOAL_METRIC[$goal] ?? 'ctr';
    }

    /**
     * @param array $variants
     * @param string $metric
     * @return string|null
     */
    private function leadingVariant(array $variants, string $metric): ?string
    {
        $leader = null;
        $best = -1.0;
        foreach ($variants as $variant) {
            $score = (float)($variant[$metric] ?? 0);
            if ($score > $best) {
                $best = $score;
                $leader = $variant['variant'];
            }
        }
        return $leader;
    }

    private function scoreOf(array $variants, string $variantLabel, string $metric): float
    {
        foreach ($variants as $variant) {
            if ($variant['variant'] === $variantLabel) {
                return (float)($variant[$metric] ?? 0);
            }
        }
        return 0.0;
    }
}
