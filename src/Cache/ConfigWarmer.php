<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EasyCorp\Bundle\EasyAdminBundle\Cache;

use EasyCorp\Bundle\EasyAdminBundle\Configuration\ConfigManager;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * Ensures that the backend configuration is fully processed before executing
 * the application for the first time.
 */
class ConfigWarmer implements CacheWarmerInterface
{
    /** @var ConfigManager */
    private $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function warmUp($cacheDir)
    {
        try {
            // this forces the full processing of the backend configuration
            $this->configManager->getBackendConfig();
        } catch (\PDOException $e) {
            // this occurs for example when the database doesn't exist yet and the
            // project is being installed ('composer install' clears the cache at the end)
            // ignore this error at this point and display an error message later
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isOptional()
    {
        return false;
    }
}

class_alias('EasyCorp\Bundle\EasyAdminBundle\Cache\ConfigWarmer', 'JavierEguiluz\Bundle\EasyAdminBundle\Cache\ConfigWarmer', false);
