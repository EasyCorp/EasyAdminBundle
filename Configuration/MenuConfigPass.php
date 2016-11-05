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
    /**
     * array_merge_recursive does indeed merge arrays, but it converts values with duplicate
     * keys to arrays rather than overwriting the value in the first array with the duplicate
     * value in the second array, as array_merge does. I.e., with array_merge_recursive,
     * this happens (documented behavior):
     *
     * array_merge_recursive(array('key' => 'org value'), array('key' => 'new value'));
     *     => array('key' => array('org value', 'new value'));
     *
     * array_merge_recursive_distinct does not change the datatypes of the values in the arrays.
     * Matching keys' values in the second array overwrite those in the first array, as is the
     * case with array_merge, i.e.:
     *
     * array_merge_recursive_distinct(array('key' => 'org value'), array('key' => 'new value'));
     *     => array('key' => array('new value'));
     *
     * Parameters are passed by reference, though only for performance reasons. They're not
     * altered by this function.
     *
     * the original function taken from http://php.net/manual/en/function.array-merge-recursive.php#92195
     * but then was slightly modified
     *
     * @param array $array1
     * @param array $array2
     * @return array
     * @author Daniel <daniel (at) danielsmedegaardbuus (dot) dk>
     * @author Gabriel Sobrinho <gabriel (dot) sobrinho (at) gmail (dot) com>
     */
    function array_merge_recursive_distinct ( array &$array1, array &$array2 )
    {
        $merged = $array1;

        foreach ( $array2 as $key => &$value )
        {
            if ( is_array ( $value ) && isset ( $merged [$key] ) && is_array ( $merged [$key] ))
            {
                $merged [$key] = $this->array_merge_recursive_distinct($merged [$key], $value);
            }
            else
            {
                if (!is_string($key)) {
                    $merged[] = $value;
                }else{
                    $merged[$key] = $value;
                }
            }
        }

        return $merged;
    }

    public function process(array $backendConfig)
    {
        $menu = [];
        foreach ($backendConfig['design']['menu'] as $item) {
            if (isset($item['id'])){
                if (array_key_exists($item['id'],$menu)) {
                    $menu[$item['id']] = $this->array_merge_recursive_distinct($menu[$item['id']], $item);
                }else{
                    $menu[$item['id']] = $item;
                }
            }else{
                $menu[] = $item;
            }
        }

        // process 1st level menu items
        $menuConfig = $menu; //$backendConfig['design']['menu'];
        $menuConfig = $this->normalizeMenuConfig($menuConfig, $backendConfig);
        $menuConfig = $this->processMenuConfig($menuConfig, $backendConfig);

        $backendConfig['design']['menu'] = $menuConfig;

        // process 2nd level menu items (i.e. submenus)
        foreach ($backendConfig['design']['menu'] as $i => $itemConfig) {
            if (empty($itemConfig['children'])) {
                continue;
            }

            $submenuConfig = $itemConfig['children'];
            $submenuConfig = $this->normalizeMenuConfig($submenuConfig, $backendConfig, $i);
            $submenuConfig = $this->processMenuConfig($submenuConfig, $backendConfig, $i);

            $backendConfig['design']['menu'][$i]['children'] = $submenuConfig;
        }

        return $backendConfig;
    }

    /**
     * Normalizes the different shortcut notations of the menu config to simplify
     * further processing.
     *
     * @param array $menuConfig
     * @param array $backendConfig
     * @param int   $parentItemIndex The index of the parent item for this menu item (allows to treat submenus differently)
     *
     * @return array
     */
    private function normalizeMenuConfig(array $menuConfig, array $backendConfig, $parentItemIndex = -1)
    {
        // if the backend doesn't define the menu configuration: create a default
        // menu configuration to display all its entities
        if (empty($menuConfig)) {
            foreach ($backendConfig['entities'] as $entityName => $entityConfig) {
                $menuConfig[] = array('entity' => $entityName, 'label' => $entityConfig['label']);
            }
        }

        // replaces the short config syntax:
        //   design.menu: ['Product', 'User']
        // by the expanded config syntax:
        //   design.menu: [{ entity: 'Product' }, { entity: 'User' }]
        foreach ($menuConfig as $i => $itemConfig) {
            if (is_string($itemConfig)) {
                $itemConfig = array('entity' => $itemConfig);
            }

            $menuConfig[$i] = $itemConfig;
        }

        foreach ($menuConfig as $i => $itemConfig) {
            // normalize icon configuration
            if (!array_key_exists('icon', $itemConfig)) {
                $itemConfig['icon'] = ($parentItemIndex > -1) ? 'fa-chevron-right' : 'fa-chevron-circle-right';
            } elseif (empty($itemConfig['icon'])) {
                $itemConfig['icon'] = null;
            } else {
                $itemConfig['icon'] = 'fa-'.$itemConfig['icon'];
            }

            // normalize submenu configuration (only for main menu items)
            if (!isset($itemConfig['children']) && $parentItemIndex === -1) {
                $itemConfig['children'] = array();
            }

            // normalize 'default' option, which sets the menu item used as the backend index
            if (!array_key_exists('default', $itemConfig)) {
                $itemConfig['default'] = false;
            } else {
                $itemConfig['default'] = (bool) $itemConfig['default'];
            }

            // normalize 'target' option, which allows to open menu items in different windows or tabs
            if (!array_key_exists('target', $itemConfig)) {
                $itemConfig['target'] = false;
            } else {
                $itemConfig['target'] = (string) $itemConfig['target'];
            }

            $menuConfig[$i] = $itemConfig;
        }

        return $menuConfig;
    }

    private function processMenuConfig(array $menuConfig, array $backendConfig, $parentItemIndex = -1)
    {
        foreach ($menuConfig as $i => $itemConfig) {
            // these options are needed to find the active menu/submenu item in the template
            $itemConfig['menu_index'] = ($parentItemIndex === -1) ? $i : $parentItemIndex;
            $itemConfig['submenu_index'] = ($parentItemIndex === -1) ? -1 : $i;

            // 1st level priority: if 'entity' is defined, link to the given entity
            if (isset($itemConfig['entity'])) {
                $itemConfig['type'] = 'entity';
                $entityName = $itemConfig['entity'];

                if (!array_key_exists($entityName, $backendConfig['entities'])) {
                    throw new \RuntimeException(sprintf('The "%s" entity included in the "menu" option is not managed by EasyAdmin. The menu can only include any of these entities: %s. NOTE: If your menu worked before, this error may be caused by a change introduced by EasyAdmin 1.12.0 version. Check out https://github.com/javiereguiluz/EasyAdminBundle/releases/tag/v1.12.0 for more details.', $entityName, implode(', ', array_keys($backendConfig['entities']))));
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
                    throw new \RuntimeException(sprintf('The configuration of the menu item with "url = %s" must define the "label" option.', $itemConfig['url']));
                }
            }

            // 3rd level priority: if 'route' is defined, link to the path generated with the given route
            elseif (isset($itemConfig['route'])) {
                $itemConfig['type'] = 'route';

                if (!isset($itemConfig['label'])) {
                    throw new \RuntimeException(sprintf('The configuration of the menu item with "route = %s" must define the "label" option.', $itemConfig['route']));
                }

                if (!isset($itemConfig['params'])) {
                    $itemConfig['params'] = array();
                }
            }

            // 4th level priority: if 'label' is defined (and not the previous options),
            // this is a menu divider of a submenu title
            elseif (isset($itemConfig['label'])) {
                if (empty($itemConfig['children'])) {
                    // if the item doesn't define a submenu, this is a menu divider
                    $itemConfig['type'] = 'divider';
                } else {
                    // if the item defines a submenu, this is the title of that submenu
                    $itemConfig['type'] = 'empty';
                }
            } else {
                throw new \RuntimeException(sprintf('The configuration of the menu item in the position %d (being 0 the first item) must define at least one of these options: entity, url, route, label.', $i));
            }

            $menuConfig[$i] = $itemConfig;
        }

        return $menuConfig;
    }
}
