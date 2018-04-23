<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Configuration;

use EasyCorp\Bundle\EasyAdminBundle\Exception\UndefinedEntityException;

/**
 * Manages the loading and processing of backend configuration and it provides
 * useful methods to get the configuration for the entire backend, for a single
 * entity, for a single action, etc.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface ConfigManagerInterface
{
    /**
     * Returns the entire backend configuration or just the configuration for
     * the optional property path. Example: getBackendConfig('design.menu').
     *
     * @param string|null $propertyPath
     *
     * @return array|string
     */
    public function getBackendConfig(string $propertyPath = null);

    /**
     * Returns the configuration for the given entity name.
     *
     * @param string $entityName
     *
     * @return array The full entity configuration
     *
     * @throws UndefinedEntityException
     */
    public function getEntityConfig(string $entityName): array;

    /**
     * Returns the full entity config for the given entity class.
     *
     * @param string $fqcn The full qualified class name of the entity
     *
     * @return array|null The full entity configuration
     */
    public function getEntityConfigByClass(string $fqcn): ?array;

    /**
     * Returns the full action configuration for the given 'entity' and 'view'.
     *
     * @param string $entityName
     * @param string $view
     * @param string $action
     *
     * @return array
     */
    public function getActionConfig(string $entityName, string $view, string $action): array;

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
    public function isActionEnabled(string $entityName, string $view, string $action): bool;
}
