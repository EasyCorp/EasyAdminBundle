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

/**
 * Completes the entities configuration with the information that can only be
 * determined during runtime, not during the container compilation phase (most
 * of the entities configuration is resolved in EasyAdminExtension class)
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class Configurator
{
    public function __construct(array $backendConfig)
    {
        $this->backendConfig = $backendConfig;
    }

    /**
     * Exposes the backend configuration to any external method that needs it.
     *
     * @return array
     */
    public function getBackendConfig()
    {
        return $this->backendConfig;
    }

    /**
     * Processes and returns the full configuration for the given entity name.
     * This configuration includes all the information about the form fields
     * and properties of the entity.
     *
     * @param string $entityName
     *
     * @return array The full entity configuration
     *
     * @throws \InvalidArgumentException when the entity isn't managed by EasyAdmin
     */
    public function getEntityConfiguration($entityName)
    {
        if (!isset($this->backendConfig['entities'][$entityName])) {
            throw new \InvalidArgumentException(sprintf('Entity "%s" is not managed by EasyAdmin.', $entityName));
        }

        return $this->backendConfig['entities'][$entityName];
    }
}
