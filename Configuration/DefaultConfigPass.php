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
        $backendConfig = $this->processDefaultEntity($backendConfig);
        $backendConfig = $this->processDefaultMenuItem($backendConfig);

        return $backendConfig;
    }

    /**
     * Finds the default entity to display when the backend index is not
     * defined explicitly.
     */
    private function processDefaultEntity(array $backendConfig)
    {
        $entityNames = array_keys($backendConfig['entities']);
        $firstEntityName = isset($entityNames[0]) ? $entityNames[0] : null;
        $backendConfig['default_entity_name'] = $firstEntityName;

        return $backendConfig;
    }

    /**
     * Finds the default menu item to display when browsing the backend index.
     */
    private function processDefaultMenuItem(array $backendConfig)
    {
        $defaultMenuItem = $this->findDefaultMenuItem($backendConfig['design']['menu']);

        if ('empty' === $defaultMenuItem['type']) {
            throw new \RuntimeException(sprintf('The "menu" configuration sets "%s" as the default item, which is not possible because its type is "empty" and it cannot redirect to a valid URL.', $defaultMenuItem['label']));
        }

        $backendConfig['default_menu_item'] = $defaultMenuItem;

        return $backendConfig;
    }

    /**
     * Finds the first menu item whose 'default' option is 'true' (if any).
     * It looks for the option both in the first level items and in the
     * submenu items.
     */
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
