<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Helper;

/**
 * @author Pierre-Charles Bertineau <pc.bertineau@alterphp.com>
 */
class MenuHelper
{
    /**
     * @var \EasyCorp\Bundle\EasyAdminBundle\Security\AdminAuthorizationChecker
     */
    protected $adminAuthorizationChecker;
    /**
     * @var \Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface
     */
    protected $authorizationChecker;

    /**
     * MenuHelper constructor.
     *
     * @param \EasyCorp\Bundle\EasyAdminBundle\Security\AdminAuthorizationChecker          $adminAuthorizationChecker
     * @param \Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface $authorizationChecker
     */
    public function __construct($adminAuthorizationChecker, $authorizationChecker)
    {
        $this->adminAuthorizationChecker = $adminAuthorizationChecker;
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * Prune unauthorized menu items
     *
     * @param  array  $menuConfig
     * @param  array  $entitiesConfig
     *
     * @return array
     */
    public function pruneMenuItems(array $menuConfig, array $entitiesConfig)
    {
        $menuConfig = $this->pruneAccessDeniedEntries($menuConfig, $entitiesConfig);
        $menuConfig = $this->pruneEmptyFolderEntries($menuConfig);
        $menuConfig = $this->reindexMenuEntries($menuConfig);

        return $menuConfig;
    }

    protected function pruneAccessDeniedEntries(array $menuConfig, array $entitiesConfig)
    {
        foreach ($menuConfig as $key => $entry) {
            if (
                'entity' === $entry['type']
                && isset($entry['entity'])
                && !$this->adminAuthorizationChecker->isEasyAdminGranted(
                    $entitiesConfig[$entry['entity']],
                    isset($entry['params']) && isset($entry['params']['action']) ? $entry['params']['action'] : 'list'
                )
            ) {
                unset($menuConfig[$key]);
                continue;
            } elseif (isset($entry['role']) && !$this->authorizationChecker->isGranted($entry['role'])) {
                unset($menuConfig[$key]);
                continue;
            }

            if (isset($entry['children']) && \is_array($entry['children'])) {
                $menuConfig[$key]['children'] = $this->pruneAccessDeniedEntries($entry['children'], $entitiesConfig);
            }
        }

        return \array_values($menuConfig);
    }

    protected function pruneEmptyFolderEntries(array $menuConfig)
    {
        foreach ($menuConfig as $key => $entry) {
            if (isset($entry['children'])) {
                // Starts with sub-nodes in order to empty after possible children pruning...
                $menuConfig[$key]['children'] = $this->pruneEmptyFolderEntries($entry['children']);

                if ('empty' === $entry['type'] && empty($entry['children'])) {
                    unset($menuConfig[$key]);
                    continue;
                }
            }
        }

        return \array_values($menuConfig);
    }

    protected function reindexMenuEntries($menuConfig)
    {
        foreach ($menuConfig as $key => $firstLevelItem) {
            $menuConfig[$key]['menu_index'] = $key;
            $menuConfig[$key]['submenu_index'] = -1;

            if (isset($menuConfig[$key]['children']) && !empty($menuConfig[$key]['children'])) {
                foreach ($menuConfig[$key]['children'] as $subkey => $secondLevelItem) {
                    $menuConfig[$key]['children'][$subkey]['menu_index'] = $key;
                    $menuConfig[$key]['children'][$subkey]['submenu_index'] = $subkey;
                }
            }
        }

        return $menuConfig;
    }
}
