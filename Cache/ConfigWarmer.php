<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JavierEguiluz\Bundle\EasyAdminBundle\Cache;

use JavierEguiluz\Bundle\EasyAdminBundle\Configuration\ConfigManager;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * Ensures that the backend configuration is fully processed before executing
 * the application for the first time.
 */
class ConfigWarmer implements CacheWarmerInterface
{
    private $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    public function warmUp($cacheDir)
    {
        // this forces the full processing of the backend configuration
        $this->configManager->getBackendConfig();
    }

    public function isOptional()
    {
        return false;
    }
}
