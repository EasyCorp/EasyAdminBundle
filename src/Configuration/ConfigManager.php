<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EasyCorp\Bundle\EasyAdminBundle\Configuration;

use EasyCorp\Bundle\EasyAdminBundle\Cache\CacheManager;
use EasyCorp\Bundle\EasyAdminBundle\Exception\UndefinedEntityException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Manages the loading and processing of backend configuration and it provides
 * useful methods to get the configuration for the entire backend, for a single
 * entity, for a single action, etc.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class ConfigManager
{
    /** @var array */
    private $backendConfig;
    /** @var CacheManager */
    private $cacheManager;
    /** @var PropertyAccessorInterface */
    private $propertyAccessor;
    /** @var array */
    private $originalBackendConfig;
    /** @var ConfigPassInterface[] */
    private $configPasses;
    /** @var bool */
    private $debug;

    public function __construct(CacheManager $cacheManager, PropertyAccessorInterface $propertyAccessor, array $originalBackendConfig, $debug)
    {
        $this->cacheManager = $cacheManager;
        $this->propertyAccessor = $propertyAccessor;
        $this->originalBackendConfig = $originalBackendConfig;
        $this->debug = $debug;
    }

    /**
     * @param ConfigPassInterface $configPass
     */
    public function addConfigPass(ConfigPassInterface $configPass)
    {
        $this->configPasses[] = $configPass;
    }

    /**
     * Returns the entire backend configuration or just the configuration for
     * the optional property path. Example: getBackendConfig('design.menu').
     *
     * @param string|null $propertyPath
     *
     * @return array
     */
    public function getBackendConfig($propertyPath = null)
    {
        if (null === $this->backendConfig) {
            $this->backendConfig = $this->processConfig();
        }

        if (empty($propertyPath)) {
            return $this->backendConfig;
        }

        // turns 'design.menu' into '[design][menu]', the format required by PropertyAccess
        $propertyPath = '['.str_replace('.', '][', $propertyPath).']';

        return $this->propertyAccessor->getValue($this->backendConfig, $propertyPath);
    }

    /**
     * Returns the configuration for the given entity name.
     *
     * @param string $entityName
     *
     * @deprecated Use getEntityConfig()
     *
     * @return array The full entity configuration
     *
     * @throws \InvalidArgumentException when the entity isn't managed by EasyAdmin
     */
    public function getEntityConfiguration($entityName)
    {
        return $this->getEntityConfig($entityName);
    }

    /**
     * Returns the configuration for the given entity name.
     *
     * @param string $entityName
     *
     * @return array The full entity configuration
     *
     * @throws \InvalidArgumentException
     */
    public function getEntityConfig($entityName)
    {
        $backendConfig = $this->getBackendConfig();
        if (!isset($backendConfig['entities'][$entityName])) {
            throw new UndefinedEntityException(array('entity_name' => $entityName));
        }

        return $backendConfig['entities'][$entityName];
    }

    /**
     * Returns the full entity config for the given entity class.
     *
     * @param string $fqcn The full qualified class name of the entity
     *
     * @return array|null The full entity configuration
     */
    public function getEntityConfigByClass($fqcn)
    {
        $backendConfig = $this->getBackendConfig();
        foreach ($backendConfig['entities'] as $entityName => $entityConfig) {
            if ($entityConfig['class'] === $fqcn) {
                return $entityConfig;
            }
        }
    }

    /**
     * Returns the full action configuration for the given 'entity' and 'view'.
     *
     * @param string $entityName
     * @param string $view
     * @param string $action
     *
     * @return array
     */
    public function getActionConfig($entityName, $view, $action)
    {
        try {
            $entityConfig = $this->getEntityConfig($entityName);
        } catch (\Exception $e) {
            $entityConfig = array();
        }

        return isset($entityConfig[$view]['actions'][$action]) ? $entityConfig[$view]['actions'][$action] : array();
    }

    /**
     * Checks whether the given 'action' is enabled for the given 'entity' and
     * 'view'.
     *
     * @param string $entityName
     * @param string $view
     * @param string $action
     *
     * @return bool
     */
    public function isActionEnabled($entityName, $view, $action)
    {
        $entityConfig = $this->getEntityConfig($entityName);

        return !in_array($action, $entityConfig['disabled_actions']) && array_key_exists($action, $entityConfig[$view]['actions']);
    }

    /**
     * It processes the original backend configuration defined by the end-users
     * to generate the full configuration used by the application. Depending on
     * the environment, the configuration is processed every time or once and
     * the result cached for later reuse.
     *
     * @return array
     */
    private function processConfig()
    {
        if (true === $this->debug) {
            return $this->doProcessConfig($this->originalBackendConfig);
        }

        if ($this->cacheManager->hasItem('processed_config')) {
            return $this->cacheManager->getItem('processed_config');
        }

        $backendConfig = $this->doProcessConfig($this->originalBackendConfig);
        $this->cacheManager->save('processed_config', $backendConfig);

        return $backendConfig;
    }

    /**
     * It processes the given backend configuration to generate the fully
     * processed configuration used in the application.
     *
     * @param array $backendConfig
     *
     * @return array
     */
    private function doProcessConfig($backendConfig)
    {
        foreach ($this->configPasses as $configPass) {
            $backendConfig = $configPass->process($backendConfig);
        }

        return $backendConfig;
    }
}

class_alias('EasyCorp\Bundle\EasyAdminBundle\Configuration\ConfigManager', 'JavierEguiluz\Bundle\EasyAdminBundle\Configuration\ConfigManager', false);
