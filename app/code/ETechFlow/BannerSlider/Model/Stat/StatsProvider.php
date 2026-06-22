<?php
declare(strict_types=1);

namespace ETechFlow\BannerSlider\Model\Stat;

use Magento\Framework\App\ResourceConnection;

/**
 * Read-side aggregation of the banner stat table for the admin dashboard / CSV.
 */
class StatsProvider
{
    public function __construct(
        private readonly ResourceConnection $resourceConnection
    ) {
    }

    /**
     * Per-banner totals within a date range, newest impressions first.
     *
     * @param string $from Y-m-d
     * @param string $to Y-m-d
     * @return array<int, array{banner_id:int,name:string,type:string,impressions:int,clicks:int,add_to_cart:int,orders:int,revenue:float,ctr:float}>
     */
    public function getPerBanner(string $from, string $to): array
    {
        $connection = $this->resourceConnection->getConnection();
        $statTable = $this->resourceConnection->getTableName(StatRecorder::TABLE);
        $bannerTable = $this->resourceConnection->getTableName('etechflow_bannerslider_banner');

        $select = $connection->select()
            ->from(['s' => $statTable], [
                'banner_id' => 's.banner_id',
                'impressions' => new \Zend_Db_Expr("SUM(CASE WHEN s.event_type = 'impression' THEN s.cnt ELSE 0 END)"),
                'clicks' => new \Zend_Db_Expr("SUM(CASE WHEN s.event_type = 'click' THEN s.cnt ELSE 0 END)"),
                'add_to_cart' => new \Zend_Db_Expr("SUM(CASE WHEN s.event_type = 'add_to_cart' THEN s.cnt ELSE 0 END)"),
                'orders' => new \Zend_Db_Expr("SUM(CASE WHEN s.event_type = 'order' THEN s.cnt ELSE 0 END)"),
                'revenue' => new \Zend_Db_Expr("SUM(CASE WHEN s.event_type = 'order' THEN s.revenue ELSE 0 END)"),
            ])
            ->joinLeft(['b' => $bannerTable], 'b.banner_id = s.banner_id', ['name' => 'b.name', 'type' => 'b.type'])
            ->where('s.stat_date >= ?', $from)
            ->where('s.stat_date <= ?', $to)
            ->group('s.banner_id')
            ->order('impressions DESC');

        $rows = [];
        foreach ($connection->fetchAll($select) as $row) {
            $impressions = (int)$row['impressions'];
            $clicks = (int)$row['clicks'];
            $rows[] = [
                'banner_id' => (int)$row['banner_id'],
                'name' => (string)($row['name'] ?? __('(deleted banner #%1)', $row['banner_id'])),
                'type' => (string)($row['type'] ?? ''),
                'impressions' => $impressions,
                'clicks' => $clicks,
                'add_to_cart' => (int)$row['add_to_cart'],
                'orders' => (int)$row['orders'],
                'revenue' => (float)$row['revenue'],
                'ctr' => $impressions > 0 ? round($clicks / $impressions * 100, 2) : 0.0,
            ];
        }
        return $rows;
    }

    /**
     * Per-variant totals for a single slider (drives A/B reporting + winner
     * resolution). Includes derived rates so callers compare fairly across
     * variants that received different traffic.
     *
     * @param int $sliderId
     * @param string $from Y-m-d
     * @param string $to Y-m-d
     * @return array<int, array{variant:string,impressions:int,clicks:int,add_to_cart:int,orders:int,revenue:float,ctr:float,atc_rate:float,rev_per_impression:float}>
     */
    public function getPerVariant(int $sliderId, string $from, string $to): array
    {
        $connection = $this->resourceConnection->getConnection();
        $statTable = $this->resourceConnection->getTableName(StatRecorder::TABLE);

        $select = $connection->select()
            ->from(['s' => $statTable], [
                'variant' => 's.variant',
                'impressions' => new \Zend_Db_Expr("SUM(CASE WHEN s.event_type = 'impression' THEN s.cnt ELSE 0 END)"),
                'clicks' => new \Zend_Db_Expr("SUM(CASE WHEN s.event_type = 'click' THEN s.cnt ELSE 0 END)"),
                'add_to_cart' => new \Zend_Db_Expr("SUM(CASE WHEN s.event_type = 'add_to_cart' THEN s.cnt ELSE 0 END)"),
                'orders' => new \Zend_Db_Expr("SUM(CASE WHEN s.event_type = 'order' THEN s.cnt ELSE 0 END)"),
                'revenue' => new \Zend_Db_Expr("SUM(CASE WHEN s.event_type = 'order' THEN s.revenue ELSE 0 END)"),
            ])
            ->where('s.slider_id = ?', $sliderId)
            ->where('s.stat_date >= ?', $from)
            ->where('s.stat_date <= ?', $to)
            ->group('s.variant')
            ->order('impressions DESC');

        $rows = [];
        foreach ($connection->fetchAll($select) as $row) {
            $impressions = (int)$row['impressions'];
            $clicks = (int)$row['clicks'];
            $addToCart = (int)$row['add_to_cart'];
            $revenue = (float)$row['revenue'];
            $rows[] = [
                'variant' => (string)$row['variant'],
                'impressions' => $impressions,
                'clicks' => $clicks,
                'add_to_cart' => $addToCart,
                'orders' => (int)$row['orders'],
                'revenue' => $revenue,
                'ctr' => $impressions > 0 ? round($clicks / $impressions * 100, 2) : 0.0,
                'atc_rate' => $impressions > 0 ? round($addToCart / $impressions * 100, 2) : 0.0,
                'rev_per_impression' => $impressions > 0 ? round($revenue / $impressions, 4) : 0.0,
            ];
        }
        return $rows;
    }

    /**
     * Headline totals for the KPI cards.
     *
     * @param string $from Y-m-d
     * @param string $to Y-m-d
     * @return array{impressions:int,clicks:int,add_to_cart:int,orders:int,revenue:float,ctr:float,cvr:float}
     */
    public function getKpis(string $from, string $to): array
    {
        $impressions = $clicks = $addToCart = $orders = 0;
        $revenue = 0.0;
        foreach ($this->getPerBanner($from, $to) as $row) {
            $impressions += $row['impressions'];
            $clicks += $row['clicks'];
            $addToCart += $row['add_to_cart'];
            $orders += $row['orders'];
            $revenue += $row['revenue'];
        }

        return [
            'impressions' => $impressions,
            'clicks' => $clicks,
            'add_to_cart' => $addToCart,
            'orders' => $orders,
            'revenue' => $revenue,
            'ctr' => $impressions > 0 ? round($clicks / $impressions * 100, 2) : 0.0,
            'cvr' => $clicks > 0 ? round($orders / $clicks * 100, 2) : 0.0,
        ];
    }
}
