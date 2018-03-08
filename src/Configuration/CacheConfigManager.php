<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Configuration;

use Psr\Cache\CacheItemPoolInterface;

/**
 * @author Konstantin Grachev <me@grachevko.ru>
 */
final class CacheConfigManager implements ConfigManagerInterface
{
    private const CACHE_KEY = 'easyadmin.processed_config';

    /**
     * @var ConfigManagerInterface
     */
    private $configManager;

    /**
     * @var CacheItemPoolInterface
     */
    private $cache;

    /**
     * @var bool
     */
    private $debug;

    public function __construct(ConfigManagerInterface $configManager, CacheItemPoolInterface $cache, bool $debug)
    {
        $this->configManager = $configManager;
        $this->cache = $cache;
        $this->debug = $debug;
    }

    /**
     * {@inheritdoc}
     */
    public function getBackendConfig(string $propertyPath = null)
    {
        if (true === $this->debug) {
            return $this->configManager->getBackendConfig($propertyPath);
        }

        $item = $this->cache->getItem(self::CACHE_KEY.'.'.$propertyPath);

        if (!$item->isHit()) {
            $item->set($this->configManager->getBackendConfig($propertyPath));
            $this->cache->save($item);
        }

        return $item->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityConfig(string $entityName): array
    {
        return $this->configManager->getEntityConfig($entityName);
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityConfigByClass(string $fqcn): ?array
    {
        return $this->configManager->getEntityConfigByClass($fqcn);
    }

    /**
     * {@inheritdoc}
     */
    public function getActionConfig(string $entityName, string $view, string $action): array
    {
        return $this->configManager->getActionConfig($entityName, $view, $action);
    }

    /**
     * {@inheritdoc}
     */
    public function isActionEnabled(string $entityName, string $view, string $action): bool
    {
        return $this->configManager->isActionEnabled($entityName, $view, $action);
    }
}
