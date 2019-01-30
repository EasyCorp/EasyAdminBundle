<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AdminAuthorizationExtension extends AbstractExtension
{
    /**
     * @var \EasyCorp\Bundle\EasyAdminBundle\Security\AdminAuthorizationChecker
     */
    protected $adminAuthorizationChecker;

    /**
     * @var \EasyCorp\Bundle\EasyAdminBundle\Helper\MenuHelper
     */
    protected $menuHelper;

    public function __construct($adminAuthorizationChecker, $menuHelper)
    {
        $this->adminAuthorizationChecker = $adminAuthorizationChecker;
        $this->menuHelper = $menuHelper;
    }

    public function getFilters()
    {
        return [
            new TwigFilter('prune_item_actions', [$this, 'pruneItemsActions']),
            new TwigFilter('prune_menu_items', [$this, 'pruneMenuItems']),
        ];
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('is_easyadmin_granted', [$this, 'isEasyAdminGranted']),
        ];
    }

    public function isEasyAdminGranted(array $entityConfig, string $actionName = 'list', $subject = null)
    {
        return $this->adminAuthorizationChecker->isEasyAdminGranted($entityConfig, $actionName, $subject);
    }

    public function pruneItemsActions(
        array $itemActions, array $entityConfig, $subject = null, array $forbiddenActions = []
    ) {
        return \array_filter($itemActions, function ($action) use ($entityConfig, $subject, $forbiddenActions) {
            return !\in_array($action, $forbiddenActions)
                && $this->isEasyAdminGranted($entityConfig, $action, $subject);
        }, ARRAY_FILTER_USE_KEY);
    }

    public function pruneMenuItems(array $menuConfig, array $entitiesConfig)
    {
        return $this->menuHelper->pruneMenuItems($menuConfig, $entitiesConfig);
    }
}
