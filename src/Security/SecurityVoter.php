<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Security;

use EasyCorp\Bundle\EasyAdminBundle\Dto\ActionDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\MenuItemDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\PropertyDto;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class SecurityVoter extends Voter
{
    private $authorizationChecker;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
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

        if (Permission::EA_VIEW_PROPERTY === $permissionName) {
            return $this->voteOnViewPropertyPermission($subject);
        }

        if (Permission::EA_VIEW_ENTITY === $permissionName) {
            return $this->voteOnViewEntityPermission($subject);
        }

        if (Permission::EA_VIEW_ACTION === $permissionName) {
            return $this->voteOnViewActionPermission($subject);
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

    private function voteOnViewPropertyPermission(PropertyDto $propertyDto): bool
    {
        // users can see the property if they have the permission required by the property
        return $this->authorizationChecker->isGranted($propertyDto->getPermission());
    }

    private function voteOnViewEntityPermission(EntityDto $entityDto): bool
    {
        // users can see the entity if they have the required permission on the specific entity instance
        return $this->authorizationChecker->isGranted($entityDto->getPermission(), $entityDto->getInstance());
    }

    private function voteOnViewActionPermission(ActionDto $actionDto): bool
    {
        // users can see the action if they have the permission required by the action
        return $this->authorizationChecker->isGranted($actionDto->getPermission());
    }

    private function voteOnExitImpersonationPermission(): bool
    {
        // users can exit impersonation if they are currently impersonating another user.
        // In Symfony, that means that current user has the special 'ROLE_PREVIOUS_ADMIN' permission
        return $this->authorizationChecker->isGranted('ROLE_PREVIOUS_ADMIN');
    }
}
