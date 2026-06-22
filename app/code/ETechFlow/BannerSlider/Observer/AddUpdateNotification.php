<?php

declare(strict_types=1);

namespace ETechFlow\BannerSlider\Observer;

use ETechFlow\BannerSlider\Model\LicenseValidator;
use ETechFlow\BannerSlider\Model\UpdateChecker;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Notification\NotifierInterface;

/**
 * Adds an admin bell-icon (AdminNotification inbox) entry when a newer version
 * of this module is published to the eTechFlow private Composer repo. Fires when
 * a Banner Slider admin page is opened.
 *
 * Re-surfacing rule: the notice is (re-)added whenever there is no active
 * (is_remove = 0) inbox row for the same version — so a dismissed notice returns
 * on the next module-page open while an update is still pending, but identical
 * rows never pile up. Fully fail-safe — never interrupts the admin page.
 */
class AddUpdateNotification implements ObserverInterface
{
    public function __construct(
        private readonly UpdateChecker $updateChecker,
        private readonly NotifierInterface $notifier,
        private readonly ResourceConnection $resource,
        private readonly LicenseValidator $licenseValidator
    ) {
    }

    public function execute(Observer $observer)
    {
        try {
            // Don't surface update notices while the licence is inactive — the
            // suspension card is shown instead.
            if (!$this->licenseValidator->isValid()) {
                return;
            }

            $update = $this->updateChecker->getAvailableUpdate();
            if ($update === null) {
                return;
            }

            $title = (string) __('eTechFlow Banner Slider %1 is available', $update['latest']);

            // Only add when no active (non-removed) inbox entry for this exact
            // version exists. A dismissed entry (is_remove = 1) is therefore
            // re-added on the next module-page open.
            $conn  = $this->resource->getConnection();
            $table = $this->resource->getTableName('adminnotification_inbox');
            $exists = (int) $conn->fetchOne(
                "SELECT COUNT(*) FROM {$table} WHERE title = ? AND is_remove = 0",
                [$title]
            );
            if ($exists > 0) {
                return;
            }

            $desc = $update['notes'] !== ''
                ? $update['notes']
                : (string) __(
                    'A new version (%1) is available — you have %2. Update with: %3',
                    $update['latest'],
                    $update['installed'],
                    $this->updateChecker->getUpdateCommand()
                );

            $this->notifier->addNotice($title, $desc);
        } catch (\Throwable $e) {
            // never interrupt the admin page
        }
    }
}
