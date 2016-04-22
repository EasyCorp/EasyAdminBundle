<?php

namespace JavierEguiluz\Bundle\EasyAdminBundle\Cache;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use JavierEguiluz\Bundle\EasyAdminBundle\Configuration\Processor;

class ConfigurationCacheWarmer implements CacheWarmerInterface
{
    private $processor;
    private $processedBackendConfigFilepath;

    public function __construct(Processor $processor, $processedBackendConfigFilepath)
    {
        $this->processor = $processor;
        $this->processedBackendConfigFilepath = $processedBackendConfigFilepath;
    }

    public function warmUp($cacheDir)
    {
        mkdir($cacheDir.'/easy_admin');
        $backendConfig = $this->processor->processConfig();
        file_put_contents($this->processedBackendConfigFilepath, serialize($backendConfig));
    }

    public function isOptional()
    {
        return false;
    }
}