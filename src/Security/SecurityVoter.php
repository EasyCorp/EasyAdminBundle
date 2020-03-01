<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Security;

use EasyCorp\Bundle\EasyAdminBundle\Context\ApplicationContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfigInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\ActionDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\CrudDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\MenuItemDto;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class SecurityVoter extends Voter
{
    private $authorizationChecker;
    private $applicationContextProvider;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker, ApplicationContextProvider $applicationContextProvider)
    {
        $this->authorizationChecker = $authorizationChecker;
        $this->applicationContextProvider = $applicationContextProvider;
    }

    protected function supports($permissionName, $subject)
    {
        return Permission::exists($permissionName);
    }

    protected function voteOnAttribute($permissionName, $subject, TokenInterface $token)
    {
        if (Permission::EA_VIEW_MENU_ITEM === $permissionName) {
            return $this->voteOnViewMenuItemPermission($subject);
        }

        if (Permission::EA_VIEW_PAGE === $permissionName) {
            return $this->voteOnViewPagePermission($this->applicationContextProvider->getContext()->getCrud());
        }

        if (Permission::EA_VIEW_PROPERTY === $permissionName) {
            return $this->voteOnViewPropertyPermission($subject);
        }

        if (Permission::EA_VIEW_ENTITY === $permissionName) {
            return $this->voteOnViewEntityPermission($subject);
        }

        if (Permission::EA_EXECUTE_ACTION === $permissionName) {
            return $this->voteOnExecuteActionPermission($subject);
        }

        if (Permission::EA_EXIT_IMPERSONATION === $permissionName) {
            return $this->voteOnExitImpersonationPermission();
        }

        return true;
    }

    private function voteOnViewMenuItemPermission(MenuItemDto $menuItemDto): bool
    {
        // users can see the menu item if they have the permission required by the menu item
        return $this->authorizationChecker->isGranted($menuItemDto->getPermission());
    }

    private function voteOnViewPagePermission(CrudDto $crudDto): bool
    {
        // users can run the Crud action if:
        // * they have the required permission to view the page
        // * the action related to the page is not disabled
        $pagePermission = $crudDto->getPagePermission($crudDto->getCurrentPage());

        return $this->authorizationChecker->isGranted($pagePermission) && !$this->isActionDisabled($crudDto->getCurrentAction());
    }

    private function voteOnViewPropertyPermission(PropertyConfigInterface $propertyConfig): bool
    {
        // users can see the property if they have the permission required by the property
        return $this->authorizationChecker->isGranted($propertyConfig->getPermission());
    }

    private function voteOnViewEntityPermission(EntityDto $entityDto): bool
    {
        // users can see the entity if they have the required permission on the specific entity instance
        return $this->authorizationChecker->isGranted($entityDto->getPermission(), $entityDto->getInstance());
    }

    private function voteOnExecuteActionPermission(ActionDto $actionDto): bool
    {
        // users can see the action if:
        // * they have the permission required by the action
        // * the action is not disabled
        return $this->authorizationChecker->isGranted($actionDto->getPermission()) && !$this->isActionDisabled($actionDto->getName());
    }

    private function voteOnExitImpersonationPermission(): bool
    {
        // users can exit impersonation if they are currently impersonating another user.
        // In Symfony, that means that current user has the special 'ROLE_PREVIOUS_ADMIN' permission
        return $this->authorizationChecker->isGranted('ROLE_PREVIOUS_ADMIN');
    }

    private function isActionDisabled(string $actionName): bool
    {
        foreach ($this->applicationContextProvider->getContext()->getCrud()->getDisabledActions() as $disabledAction) {
            if ($actionName === $disabledAction->getName()) {
                return true;
            }
        }

        return false;
    }
}
