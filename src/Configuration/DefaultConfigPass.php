<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Configuration;

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
        $backendConfig = $this->processDefaultHomepage($backendConfig);

        return $backendConfig;
    }

    /**
     * Finds the default entity to display when the backend index is not
     * defined explicitly.
     *
     * @param array $backendConfig
     *
     * @return array
     */
    private function processDefaultEntity(array $backendConfig)
    {
        $entityNames = \array_keys($backendConfig['entities']);
        $firstEntityName = $entityNames[0] ?? null;
        $backendConfig['default_entity_name'] = $firstEntityName;

        return $backendConfig;
    }

    /**
     * Finds the default menu item to display when browsing the backend index.
     *
     * @param array $backendConfig
     *
     * @return array
     */
    private function processDefaultMenuItem(array $backendConfig)
    {
        $defaultMenuItem = $this->findDefaultMenuItem($backendConfig['design']['menu']);

        if ('empty' === $defaultMenuItem['type']) {
            throw new \RuntimeException(\sprintf('The "menu" configuration sets "%s" as the default item, which is not possible because its type is "empty" and it cannot redirect to a valid URL.', $defaultMenuItem['label']));
        }

        $backendConfig['default_menu_item'] = $defaultMenuItem;

        return $backendConfig;
    }

    /**
     * Finds the first menu item whose 'default' option is 'true' (if any).
     * It looks for the option both in the first level items and in the
     * submenu items.
     *
     * @param array $menuConfig
     *
     * @return mixed
     */
    private function findDefaultMenuItem(array $menuConfig)
    {
        foreach ($menuConfig as $itemConfig) {
            if (true === $itemConfig['default']) {
                return $itemConfig;
            }

            foreach ($itemConfig['children'] as $subitemConfig) {
                if (true === $subitemConfig['default']) {
                    return $subitemConfig;
                }
            }
        }
    }

    /**
     * Processes the backend config to define the URL or the route/params to
     * use as the default backend homepage when none is defined explicitly.
     * (Note: we store the route/params instead of generating the URL because
     * the 'router' service cannot be used inside a compiler pass).
     *
     * @param array $backendConfig
     *
     * @return array
     */
    private function processDefaultHomepage(array $backendConfig)
    {
        $backendHomepage = [];

        // if no menu item has been set as "default", use the "list"
        // action of the first configured entity as the backend homepage
        if (null === $menuItemConfig = $backendConfig['default_menu_item']) {
            $defaultEntityName = $backendConfig['default_entity_name'];
            $backendHomepage['route'] = 'easyadmin';
            $backendHomepage['params'] = ['action' => 'list', 'entity' => $defaultEntityName];

            // if the default entity defines a custom sorting, use it
            $defaultEntityConfig = isset($backendConfig['entities'][$defaultEntityName]) ? $backendConfig['entities'][$defaultEntityName] : [];
            if (isset($defaultEntityConfig['list']['sort'])) {
                $backendHomepage['params'] = \array_merge($backendHomepage['params'], [
                    'sortField' => $defaultEntityConfig['list']['sort']['field'],
                    'sortDirection' => $defaultEntityConfig['list']['sort']['direction'],
                ]);
            }
        } else {
            $routeParams = ['menuIndex' => $menuItemConfig['menu_index'], 'submenuIndex' => $menuItemConfig['submenu_index']];

            if ('entity' === $menuItemConfig['type']) {
                $backendHomepage['route'] = 'easyadmin';
                $backendHomepage['params'] = \array_merge(['action' => 'list', 'entity' => $menuItemConfig['entity']], $routeParams, $menuItemConfig['params']);
            } elseif ('route' === $menuItemConfig['type']) {
                $backendHomepage['route'] = $menuItemConfig['route'];
                $backendHomepage['params'] = \array_merge($routeParams, $menuItemConfig['params']);
            } elseif ('link' === $menuItemConfig['type']) {
                $backendHomepage['url'] = $menuItemConfig['url'];
            }
        }

        $backendConfig['homepage'] = $backendHomepage;

        return $backendConfig;
    }
}
