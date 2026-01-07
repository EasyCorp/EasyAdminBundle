<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Security;

use EasyCorp\Bundle\EasyAdminBundle\Security\Permission;
use PHPUnit\Framework\TestCase;

class PermissionTest extends TestCase
{
    /**
     * @dataProvider provideValidPermissions
     */
    public function testExistsReturnsTrueForValidPermissions(string $permission): void
    {
        $this->assertTrue(Permission::exists($permission));
    }

    public static function provideValidPermissions(): iterable
    {
        yield [Permission::EA_ACCESS_ENTITY];
        yield [Permission::EA_EXECUTE_ACTION];
        yield [Permission::EA_VIEW_MENU_ITEM];
        yield [Permission::EA_VIEW_FIELD];
        yield [Permission::EA_EXIT_IMPERSONATION];
    }

    /**
     * @dataProvider provideInvalidPermissions
     */
    public function testExistsReturnsFalseForInvalidPermissions(string $permission): void
    {
        $this->assertFalse(Permission::exists($permission));
    }

    public static function provideInvalidPermissions(): iterable
    {
        yield ['ROLE_ADMIN'];
        yield ['ROLE_USER'];
        yield ['IS_AUTHENTICATED'];
        yield ['INVALID_PERMISSION'];
        yield [''];
        yield ['EA_UNKNOWN'];
        yield ['ea_access_entity'];
    }

    public function testExistsReturnsFalseForNull(): void
    {
        $this->assertFalse(Permission::exists(null));
    }

    public function testPermissionConstantsHaveExpectedValues(): void
    {
        $this->assertSame('EA_ACCESS_ENTITY', Permission::EA_ACCESS_ENTITY);
        $this->assertSame('EA_EXECUTE_ACTION', Permission::EA_EXECUTE_ACTION);
        $this->assertSame('EA_VIEW_MENU_ITEM', Permission::EA_VIEW_MENU_ITEM);
        $this->assertSame('EA_VIEW_FIELD', Permission::EA_VIEW_FIELD);
        $this->assertSame('EA_EXIT_IMPERSONATION', Permission::EA_EXIT_IMPERSONATION);
    }
}
