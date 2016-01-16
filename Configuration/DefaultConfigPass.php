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
 * Processes default values for some backend configuration options.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class DefaultConfigPass implements ConfigPassInterface
{
    public function process(array $backendConfig)
    {
        $backendConfig = $this->processBackendIndex($backendConfig);

        return $backendConfig;
    }

    private function processBackendIndex(array $backendConfig)
    {
        // define the default entity used when there is no default menu item
        $entityNames = array_keys($backendConfig['entities']);
        $firstEntityName = isset($entityNames[0]) ? $entityNames[0] : null;
        $backendConfig['default_entity_name'] = $firstEntityName;

        // determine if there is a default menu item to be displayed as the backend index
        $defaultMenuItem = $this->findDefaultMenuItem($backendConfig['design']['menu']);
        if ('empty' === $defaultMenuItem['type']) {
            throw new \RuntimeException(sprintf('The "menu" configuration sets "%s" as the default item, which is wrong because its type is "empty" and it cannot redirect to a valid URL.', $defaultMenuItem['label']));
        }
        $backendConfig['default_menu_item'] = $defaultMenuItem;

        return $backendConfig;
    }

    private function findDefaultMenuItem(array $menuConfig)
    {
        foreach ($menuConfig as $i => $itemConfig) {
            if (true === $itemConfig['default']) {
                return $itemConfig;
            }

            if (!empty($itemConfig['children'])) {
                $this->findDefaultMenuItem($itemConfig['children']);
            }
        }
    }
}
