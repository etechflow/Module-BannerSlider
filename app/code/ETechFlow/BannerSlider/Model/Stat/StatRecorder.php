<?php
declare(strict_types=1);

namespace ETechFlow\BannerSlider\Model\Stat;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Psr\Log\LoggerInterface;

/**
 * Upserts banner events into the daily-aggregated stat table.
 *
 * One row per (banner, slider, store, variant, event_type, date); repeated
 * events of the same shape increment cnt/revenue in place via ON DUPLICATE KEY
 * UPDATE, so the table stays small regardless of traffic.
 */
class StatRecorder
{
    public const TABLE = 'etechflow_bannerslider_stat';

    /** Events the storefront beacon is allowed to record. */
    public const CLIENT_EVENTS = ['impression', 'click', 'add_to_cart'];

    /** Revenue-bearing events recorded server-side only. */
    public const SERVER_EVENTS = ['order'];

    public function __construct(
        private readonly ResourceConnection $resourceConnection,
        private readonly TimezoneInterface $timezone,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Record a batch of events for a store.
     *
     * @param array<int, array{banner_id:int,slider_id:int,variant?:string,event_type:string,cnt?:int,revenue?:float}> $events
     * @param int $storeId
     * @param string[] $allowed Event types accepted for this call
     * @return int Number of events persisted
     */
    public function record(array $events, int $storeId, array $allowed = self::CLIENT_EVENTS): int
    {
        if (!$events) {
            return 0;
        }

        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName(self::TABLE);
        $date = $this->timezone->date()->format('Y-m-d');

        $sql = "INSERT INTO {$table} "
            . '(banner_id, slider_id, store_id, variant, event_type, stat_date, cnt, revenue) '
            . 'VALUES (?, ?, ?, ?, ?, ?, ?, ?) '
            . 'ON DUPLICATE KEY UPDATE cnt = cnt + VALUES(cnt), revenue = revenue + VALUES(revenue)';

        $saved = 0;
        foreach ($events as $event) {
            $bannerId = (int)($event['banner_id'] ?? 0);
            $sliderId = (int)($event['slider_id'] ?? 0);
            $type = (string)($event['event_type'] ?? '');

            if ($bannerId <= 0 || $sliderId <= 0 || !in_array($type, $allowed, true)) {
                continue;
            }

            $cnt = max(1, (int)($event['cnt'] ?? 1));
            $revenue = round((float)($event['revenue'] ?? 0), 4);
            $variant = (string)($event['variant'] ?? 'default');
            if ($variant === '') {
                $variant = 'default';
            }

            try {
                $connection->query($sql, [
                    $bannerId, $sliderId, $storeId, $variant, $type, $date, $cnt, $revenue,
                ]);
                $saved++;
            } catch (\Throwable $e) {
                $this->logger->warning('ETechFlow BannerSlider: stat write failed: ' . $e->getMessage());
            }
        }

        return $saved;
    }
}
