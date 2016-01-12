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
 * Processes the main menu configuration defined in the "design.menu"
 * option or creates the default config for the menu if none is defined.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class MenuConfigPass implements ConfigPassInterface
{
    public function process(array $backendConfig)
    {
        $backendConfig = $this->normalizeMenuConfig($backendConfig);
        $backendConfig = $this->processMenuConfig($backendConfig);

        return $backendConfig;
    }

    private function normalizeMenuConfig(array $backendConfig)
    {
        $menuConfig = $backendConfig['design']['menu'];

        // if the backend doesn't define the menu configuration: create a default
        // menu configuration to display all its entities
        if (empty($menuConfig)) {
            foreach ($backendConfig['entities'] as $entityName => $entityConfig) {
                $menuConfig[] = array('entity' => $entityName, 'label' => $entityConfig['label']);
            }
        }

        // process the short config syntax to simplify further processing:
        // easy_admin:
        //   design:
        //     menu: ['Product', 'User']
        //
        // is transformed into:
        //
        // easy_admin:
        //   design:
        //     menu:
        //       - { entity: 'Product' }
        //       - { entity: 'User' }
        foreach ($menuConfig as $i => $itemConfig) {
            if (is_string($itemConfig)) {
                $itemConfig = array('entity' => $itemConfig);
            }

            $menuConfig[$i] = $itemConfig;
        }

        foreach ($menuConfig as $i => $itemConfig) {
            // normalize icon configuration
            if (!isset($itemConfig['icon'])) {
                $itemConfig['icon'] = null;
            } else {
                $itemConfig['icon'] = 'fa-'.$itemConfig['icon'];
            }

            // normalize children configuration (for submenus)
            if (!isset($itemConfig['children'])) {
                $itemConfig['children'] = array();
            }
            // } else {
            //     $itemConfig['children'] = $this->normalizeMenuConfig($itemConfig['children']);
            // }

            $menuConfig[$i] = $itemConfig;
        }

        $backendConfig['design']['menu'] = $menuConfig;

        return $backendConfig;
    }

    private function processMenuConfig(array $backendConfig)
    {
        $menuConfig = $backendConfig['design']['menu'];

        foreach ($menuConfig as $i => $itemConfig) {
            // 1st level priority: if 'entity' is defined, link to the given entity
            if (isset($itemConfig['entity'])) {
                $itemConfig['type'] = 'entity';
                $entityName = $itemConfig['entity'];

                if (!array_key_exists($entityName, $backendConfig['entities'])) {
                    throw new \RuntimeException(sprintf('The "%s" entity included in the "menu" option is not managed by EasyAdmin. The menu can only include any of these entities: %s.', $entityName, implode(', ', array_keys($backendConfig['entities']))));
                }

                if (!isset($itemConfig['label'])) {
                    $itemConfig['label'] = $backendConfig['entities'][$entityName]['label'];
                }

                if (!isset($itemConfig['params'])) {
                    $itemConfig['params'] = array();
                }
            }

            // 2nd level priority: if 'url' is defined, link to the given absolute/relative URL
            elseif (isset($itemConfig['url'])) {
                $itemConfig['type'] = 'link';

                if (!isset($itemConfig['label'])) {
                    throw new \RuntimeException(sprintf('The configuration of the menu item in the position %d (being 0 the first item) must define the "label" option.', $i));
                }
            }

            // 3rd level priority: if 'route' is defined, link to the path generated with the given route
            elseif (isset($itemConfig['route'])) {
                $itemConfig['type'] = 'route';

                if (!isset($itemConfig['label'])) {
                    throw new \RuntimeException(sprintf('The configuration of the menu item in the position %d (being 0 the first item) must define the "label" option.', $i));
                }

                if (!isset($itemConfig['params'])) {
                    $itemConfig['params'] = array();
                }
            }

            // 4th level priority: if 'label' is defined (and not the previous options), this is an empty element
            elseif (isset($itemConfig['label'])) {
                $itemConfig['type'] = 'empty';
            }

            else {
                throw new \RuntimeException(sprintf('The configuration of the "menu" option is wrong. The item in the position %d (being 0 the first item) must define at least one of these options: entity, url, route, label.', $i));
            }

            $menuConfig[$i] = $itemConfig;
        }

        $backendConfig['design']['menu'] = $menuConfig;

        return $backendConfig;
    }
}
