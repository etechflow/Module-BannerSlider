<?php

declare(strict_types=1);

namespace ETechFlow\BannerSlider\Block\Adminhtml;

use ETechFlow\BannerSlider\Model\LicenseValidator;
use ETechFlow\BannerSlider\Model\UpdateChecker;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;

/**
 * Renders the "update available" banner at the top of the module's admin pages.
 * When the store is on the latest version, the licence is inactive, or the repo
 * is unreachable, getUpdateInfo() returns null and the template renders nothing.
 */
class UpdateNotice extends Template
{
    public function __construct(
        Context $context,
        private readonly UpdateChecker $updateChecker,
        private readonly LicenseValidator $licenseValidator,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @return array{installed:string,latest:string,notes:string,package:string}|null
     */
    public function getUpdateInfo(): ?array
    {
        if (!$this->licenseValidator->isValid()) {
            return null;
        }
        return $this->updateChecker->getAvailableUpdate();
    }

    public function getUpdateCommand(): string
    {
        return $this->updateChecker->getUpdateCommand();
    }
}
