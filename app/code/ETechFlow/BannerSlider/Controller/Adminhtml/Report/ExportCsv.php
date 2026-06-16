<?php
declare(strict_types=1);

namespace ETechFlow\BannerSlider\Controller\Adminhtml\Report;

use ETechFlow\BannerSlider\Model\Stat\StatsProvider;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;

class ExportCsv extends Action
{
    public const ADMIN_RESOURCE = 'ETechFlow_BannerSlider::report';

    public function __construct(
        Context $context,
        private readonly FileFactory $fileFactory,
        private readonly StatsProvider $statsProvider,
        private readonly Filesystem $filesystem
    ) {
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function execute()
    {
        $from = $this->validDate((string)$this->getRequest()->getParam('from'))
            ?? date('Y-m-d', strtotime('-29 days'));
        $to = $this->validDate((string)$this->getRequest()->getParam('to')) ?? date('Y-m-d');

        $rows = $this->statsProvider->getPerBanner($from, $to);
        $name = 'export/bannerslider_stats_' . $from . '_' . $to . '.csv';

        $directory = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $stream = $directory->openFile($name, 'w+');
        $stream->lock();
        $stream->writeCsv([
            'Banner ID', 'Name', 'Type', 'Impressions', 'Clicks', 'CTR %', 'Add to Cart', 'Orders', 'Revenue',
        ]);
        foreach ($rows as $row) {
            $stream->writeCsv([
                $row['banner_id'], $row['name'], $row['type'], $row['impressions'], $row['clicks'],
                $row['ctr'], $row['add_to_cart'], $row['orders'], $row['revenue'],
            ]);
        }
        $stream->unlock();
        $stream->close();

        return $this->fileFactory->create(
            'bannerslider_stats_' . $from . '_' . $to . '.csv',
            ['type' => 'filename', 'value' => $name],
            DirectoryList::VAR_DIR,
            'text/csv'
        );
    }

    private function validDate(string $value): ?string
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : null;
    }
}
