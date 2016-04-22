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

use JavierEguiluz\Bundle\EasyAdminBundle\Configuration\Processor;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * Ensures that the backend configuration is fully processed before executing
 * the application for the first time.
 */
class ConfigurationCacheWarmer implements CacheWarmerInterface
{
    private $configProcessor;
    private $cachedConfigFilePath;

    public function __construct(Processor $configProcessor, $cachedConfigFilePath)
    {
        $this->configProcessor = $configProcessor;
        $this->cachedConfigFilePath = $cachedConfigFilePath;
    }

    public function warmUp($cacheDir)
    {
        $easyAdminCacheDir = $cacheDir.'/easy_admin';
        if (!file_exists($easyAdminCacheDir)) {
            mkdir($easyAdminCacheDir);
        }
        if (!file_exists($this->cachedConfigFilePath)) {
            touch($this->cachedConfigFilePath);
        }

        $backendConfig = $this->configProcessor->processConfig();
        file_put_contents($this->cachedConfigFilePath, serialize($backendConfig));
    }

    public function isOptional()
    {
        return false;
    }
}
