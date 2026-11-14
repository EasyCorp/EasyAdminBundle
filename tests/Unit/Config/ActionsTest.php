<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Config;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use PHPUnit\Framework\TestCase;
use Symfony\Component\ExpressionLanguage\Expression;

class ActionsTest extends TestCase
{
    public function testSetPermissionsKeepsPermissionsOfOtherActions(): void
    {
        $expression = new Expression('is_granted("ROLE_EDITOR")');
        $actions = Actions::new()
            ->setPermission(Action::DELETE, 'ROLE_ADMIN')
            ->setPermission(Action::DETAIL, $expression)
            ->setPermissions([
                Action::NEW => 'ROLE_ADMIN',
                Action::EDIT => 'ROLE_ADMIN',
            ]);

        $this->assertSame([
            Action::DELETE => 'ROLE_ADMIN',
            Action::DETAIL => $expression,
            Action::NEW => 'ROLE_ADMIN',
            Action::EDIT => 'ROLE_ADMIN',
        ], $actions->getAsDto(null)->getActionPermissions());
    }

    public function testSetPermissionsOverridesPermissionsOfTheGivenActions(): void
    {
        $actions = Actions::new()
            ->setPermissions([Action::DELETE => 'ROLE_ADMIN', Action::EDIT => 'ROLE_ADMIN'])
            ->setPermissions([Action::DELETE => 'ROLE_SUPER_ADMIN'])
            ->setPermission(Action::EDIT, 'ROLE_EDITOR');

        $this->assertSame([
            Action::DELETE => 'ROLE_SUPER_ADMIN',
            Action::EDIT => 'ROLE_EDITOR',
        ], $actions->getAsDto(null)->getActionPermissions());
    }

    public function testEmptyStringRemovesThePermissionOfAnAction(): void
    {
        $actions = Actions::new()
            ->setPermission(Action::DELETE, 'ROLE_ADMIN')
            ->setPermissions([Action::DELETE => '']);

        $this->assertSame([Action::DELETE => ''], $actions->getAsDto(null)->getActionPermissions());
    }

    public function testSetPermissionsWithEmptyArrayKeepsAllPermissions(): void
    {
        $actions = Actions::new()
            ->setPermission(Action::DELETE, 'ROLE_ADMIN')
            ->setPermissions([]);

        $this->assertSame([Action::DELETE => 'ROLE_ADMIN'], $actions->getAsDto(null)->getActionPermissions());
    }

    public function testSetPermissionsStillAcceptsNullValues(): void
    {
        $actions = Actions::new()
            ->setPermission(Action::DELETE, 'ROLE_ADMIN')
            ->setPermissions([Action::DELETE => null]);

        $this->assertSame([Action::DELETE => null], $actions->getAsDto(null)->getActionPermissions());
    }
}
