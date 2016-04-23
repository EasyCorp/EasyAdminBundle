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

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * ...
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class ConfigManager
{
    const CACHED_CONFIG_KEY = 'processed_config';

    /** @var ContainerInterface */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
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
            $this->backendConfig = $this->loadConfig();
        }

        if (empty($propertyPath)) {
            return $this->backendConfig;
        }

        // turns 'design.menu' into '[design][menu]', the format required by PropertyAccess
        $propertyPath = '['.str_replace('.', '][', $propertyPath).']';

        return $this->container->get('property_accessor')->getValue($this->backendConfig, $propertyPath);
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
     * ...
     * @return [type] [description]
     */
    public function loadConfig()
    {
        $cache = $this->container->get('easyadmin.cache.manager');

        if (true === $this->container->getParameter('kernel.debug')) {
            return $this->processConfig();
        }

        if ($cache->contains(self::CACHED_CONFIG_KEY)) {
            return $cache->fetch(self::CACHED_CONFIG_KEY);
        }

        $backendConfig = $this->processConfig();
        $cache->save(self::CACHED_CONFIG_KEY, $backendConfig);

        return $backendConfig;
    }

    /**
     * Takes the 'easyadmin.config' container parameter and turns it into the
     * fully processed configuration by applying the different "config passes"
     * in a row.
     *
     * @return array
     */
    private function processConfig()
    {
        $backendConfig = $this->container->getParameter('easyadmin.config');

        $configPasses = array(
            new NormalizerConfigPass(),
            new DesignConfigPass($this->container->get('twig'), $this->container->getParameter('kernel.debug')),
            new MenuConfigPass(),
            new ActionConfigPass(),
            new MetadataConfigPass($this->container->get('doctrine')),
            new PropertyConfigPass(),
            new ViewConfigPass(),
            new TemplateConfigPass($this->container->getParameter('kernel.root_dir').'/Resources/views'),
            new DefaultConfigPass(),
        );

        foreach ($configPasses as $configPass) {
            $backendConfig = $configPass->process($backendConfig);
        }

        return $backendConfig;
    }
}
