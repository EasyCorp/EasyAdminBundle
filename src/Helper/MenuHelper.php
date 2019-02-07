<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Helper;

use EasyCorp\Bundle\EasyAdminBundle\Security\AdminAuthorizationChecker;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @author Pierre-Charles Bertineau <pc.bertineau@alterphp.com>
 */
class MenuHelper
{
    private $adminAuthorizationChecker;
    private $authorizationChecker;

    /**
     * MenuHelper constructor.
     *
     * @param AdminAuthorizationChecker     $adminAuthorizationChecker
     * @param AuthorizationCheckerInterface $authorizationChecker
     */
    public function __construct(
        AdminAuthorizationChecker $adminAuthorizationChecker, AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->adminAuthorizationChecker = $adminAuthorizationChecker;
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * Prune unauthorized menu items.
     */
    public function pruneMenuItems(array $menuConfig, array $entitiesConfig): array
    {
        $menuConfig = $this->pruneAccessDeniedEntries($menuConfig, $entitiesConfig);
        $menuConfig = $this->pruneEmptyFolderEntries($menuConfig);
        $menuConfig = $this->reindexMenuEntries($menuConfig);

        return $menuConfig;
    }

    private function pruneAccessDeniedEntries(array $menuConfig, array $entitiesConfig)
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
            }

            if (isset($entry['role']) && !$this->authorizationChecker->isGranted($entry['role'])) {
                unset($menuConfig[$key]);
                continue;
            }

            if (isset($entry['children']) && \is_array($entry['children'])) {
                $menuConfig[$key]['children'] = $this->pruneAccessDeniedEntries($entry['children'], $entitiesConfig);
            }
        }

        return \array_values($menuConfig);
    }

    private function pruneEmptyFolderEntries(array $menuConfig)
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

    private function reindexMenuEntries($menuConfig)
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
