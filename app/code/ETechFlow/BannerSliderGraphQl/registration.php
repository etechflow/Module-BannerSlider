<?php
/**
 * ETechFlow_BannerSliderGraphQl — GraphQL API for headless / PWA / Hyvä Checkout
 *
 * @package   ETechFlow\BannerSliderGraphQl
 * @author    ETechFlow
 */
declare(strict_types=1);

use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'ETechFlow_BannerSliderGraphQl',
    __DIR__
);
