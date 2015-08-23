<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JavierEguiluz\Bundle\EasyAdminBundle\CacheWarmer;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\ClearableCache;
use JavierEguiluz\Bundle\EasyAdminBundle\Configuration\Configurator;
use Symfony\Component\HttpKernel\CacheClearer\CacheClearerInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

class CacheWarmer implements CacheWarmerInterface, CacheClearerInterface
{
    /** @var Configurator */
    private $configurator;

    /** @var Cache */
    private $cache;

    /**
     * @param Configurator $configurator
     * @param Cache        $cache
     */
    public function __construct(Configurator $configurator, Cache $cache)
    {
        $this->configurator = $configurator;
        $this->cache = $cache;
    }

    /**
     * {@inheritdoc}
     */
    public function isOptional()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function warmUp($cacheDir)
    {
        $backendConfig = $this->configurator->getBackendConfig();
        foreach ($backendConfig['entities'] as $name => $config) {
            $this->configurator->getEntityConfiguration($name);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function clear($cacheDir)
    {
        if ($this->cache instanceof ClearableCache) {
            $this->cache->deleteAll();
        }
    }
}
