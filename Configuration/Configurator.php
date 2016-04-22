<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JavierEguiluz\Bundle\EasyAdminBundle\Configuration;

use JavierEguiluz\Bundle\EasyAdminBundle\Exception\UndefinedConfigurationException;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * Exposes the configuration of the backend fully and on a per-entity basis.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class Configurator
{
    private $backendConfig;
    private $accessor;
    private $cachedConfigFilePath;

    public function __construct(PropertyAccessor $accessor, $cachedConfigFilePath)
    {
        $this->accessor = $accessor;
        $this->cachedConfigFilePath = $cachedConfigFilePath;
    }

    /**
     * Returns the entire backend configuration or just the configuration for
     * the optional property path. Example: getBackendConfig('design.menu')
     *
     * @param string $propertyPath
     *
     * @return array
     */
    public function getBackendConfig($propertyPath = null)
    {
        if (empty($this->backendConfig)) {
            $this->backendConfig = $this->loadBackendConfig();
        }

        if (empty($propertyPath)) {
            return $this->backendConfig;
        }

        // turns 'design.menu' into '[design][menu]', the format required by PropertyAccess
        $propertyPath = '['.str_replace('.', '][', $propertyPath).']';

        return $this->accessor->getValue($this->backendConfig, $propertyPath);
    }

    /**
     * Returns the configuration for the given entity name.
     *
     * @param string $entityName
     *
     * @deprecated Use getEntityConfig()
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
            throw new \InvalidArgumentException(sprintf('Entity "%s" is not managed by EasyAdmin.', $entityName));
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
        if ($view === $action) {
            return true;
        }

        $entityConfig = $this->getEntityConfig($entityName);

        return !in_array($action, $entityConfig['disabled_actions'])
            && array_key_exists($action, $entityConfig[$view]['actions']);
    }

    /**
     * This method is needed in case the cache warmer hasn't been executed
     * (usually because the developer executed "rm -fr var/cache/*"). It also
     * checks the validity of the configuration and throws an exception if needed.
     *
     * @return array
     *
     * @throws UndefinedConfigurationException if the config file doesn't exist or it's malformed
     */
    private function loadBackendConfig()
    {
        try {
            $backendConfig = unserialize(file_get_contents($this->cachedConfigFilePath));
        } catch (\Exception $e) {
            throw new UndefinedConfigurationException(array('cachedConfigFilePath' => $this->cachedConfigFilePath));
        }

        return $backendConfig;
    }
}
