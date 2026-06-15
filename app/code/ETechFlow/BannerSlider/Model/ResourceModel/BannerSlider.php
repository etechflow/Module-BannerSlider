<?php
declare(strict_types=1);

namespace ETechFlow\BannerSlider\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;

/**
 * Read/write helper for the banner<->slider link table
 * (etechflow_bannerslider_banner_slider), including A/B variant + weight.
 */
class BannerSlider
{
    public const TABLE_NAME = 'etechflow_bannerslider_banner_slider';

    public function __construct(
        private readonly ResourceConnection $resourceConnection
    ) {
    }

    /**
     * Return assignment rows for a slider, ordered by position.
     *
     * @param int $sliderId
     * @return array<int, array{banner_id:int, position:int, variant:string, weight:int}>
     */
    public function getAssignments(int $sliderId): array
    {
        if ($sliderId <= 0) {
            return [];
        }
        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName(self::TABLE_NAME);
        $select = $connection->select()
            ->from($table, ['banner_id', 'position', 'variant', 'weight'])
            ->where('slider_id = ?', $sliderId)
            ->order('position ASC');

        $rows = [];
        foreach ($connection->fetchAll($select) as $row) {
            $rows[] = [
                'banner_id' => (int)$row['banner_id'],
                'position' => (int)$row['position'],
                'variant' => (string)$row['variant'],
                'weight' => (int)$row['weight'],
            ];
        }
        return $rows;
    }

    /**
     * Return banner ids assigned to a slider, ordered by position.
     *
     * @param int $sliderId
     * @return int[]
     */
    public function getBannerIds(int $sliderId): array
    {
        return array_map(static fn ($r) => $r['banner_id'], $this->getAssignments($sliderId));
    }

    /**
     * Replace all assignments for a slider in a single transaction.
     *
     * @param int $sliderId
     * @param array $rows Each: ['banner_id'=>int, 'variant'=>?string, 'weight'=>?int]
     * @return void
     */
    public function saveAssignments(int $sliderId, array $rows): void
    {
        if ($sliderId <= 0) {
            return;
        }
        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName(self::TABLE_NAME);

        $connection->beginTransaction();
        try {
            $connection->delete($table, ['slider_id = ?' => $sliderId]);

            $position = 0;
            $insert = [];
            foreach ($rows as $row) {
                $bannerId = (int)($row['banner_id'] ?? 0);
                if ($bannerId <= 0) {
                    continue;
                }
                $insert[] = [
                    'slider_id' => $sliderId,
                    'banner_id' => $bannerId,
                    'position' => isset($row['position']) ? (int)$row['position'] : $position,
                    'variant' => $row['variant'] !== null && $row['variant'] !== ''
                        ? (string)$row['variant'] : 'default',
                    'weight' => isset($row['weight']) && (int)$row['weight'] > 0 ? (int)$row['weight'] : 1,
                ];
                $position++;
            }

            if ($insert) {
                $connection->insertMultiple($table, $insert);
            }
            $connection->commit();
        } catch (\Throwable $e) {
            $connection->rollBack();
            throw $e;
        }
    }
}
