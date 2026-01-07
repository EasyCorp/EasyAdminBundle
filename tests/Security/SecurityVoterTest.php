<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Security;

use Doctrine\ORM\Mapping\ClassMetadata;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\CrudContext;
use EasyCorp\Bundle\EasyAdminBundle\Dto\ActionConfigDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\ActionDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\CrudDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\MenuItemDto;
use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Security\Permission;
use EasyCorp\Bundle\EasyAdminBundle\Security\SecurityVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class SecurityVoterTest extends TestCase
{
    private AuthorizationCheckerInterface $authorizationChecker;
    private RequestStack $requestStack;
    private SecurityVoter $voter;

    protected function setUp(): void
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->requestStack = new RequestStack();
        $adminContextProvider = new AdminContextProvider($this->requestStack);
        $this->voter = new SecurityVoter($this->authorizationChecker, $adminContextProvider);
    }

    /**
     * @dataProvider provideValidPermissions
     */
    public function testSupportsAttributeReturnsTrueForValidPermissions(string $permission): void
    {
        $this->assertTrue($this->voter->supportsAttribute($permission));
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
    public function testSupportsAttributeReturnsFalseForInvalidPermissions(string $permission): void
    {
        $this->assertFalse($this->voter->supportsAttribute($permission));
    }

    public static function provideInvalidPermissions(): iterable
    {
        yield ['ROLE_ADMIN'];
        yield ['ROLE_USER'];
        yield ['IS_AUTHENTICATED'];
        yield ['INVALID_PERMISSION'];
        yield [''];
    }

    public function testVoteOnViewMenuItemPermissionGrantsAccessWhenUserHasPermission(): void
    {
        $menuItemDto = new MenuItemDto();
        $menuItemDto->setPermission('ROLE_ADMIN');

        $this->authorizationChecker
            ->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_ADMIN', $menuItemDto)
            ->willReturn(true);

        $token = $this->createMock(TokenInterface::class);
        $result = $this->callVoteOnAttribute(Permission::EA_VIEW_MENU_ITEM, $menuItemDto, $token);

        $this->assertTrue($result);
    }

    public function testVoteOnViewMenuItemPermissionDeniesAccessWhenUserLacksPermission(): void
    {
        $menuItemDto = new MenuItemDto();
        $menuItemDto->setPermission('ROLE_SUPER_ADMIN');

        $this->authorizationChecker
            ->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_SUPER_ADMIN', $menuItemDto)
            ->willReturn(false);

        $token = $this->createMock(TokenInterface::class);
        $result = $this->callVoteOnAttribute(Permission::EA_VIEW_MENU_ITEM, $menuItemDto, $token);

        $this->assertFalse($result);
    }

    public function testVoteOnViewMenuItemPermissionWithNullPermission(): void
    {
        $menuItemDto = new MenuItemDto();
        // no permission set (null by default)

        $this->authorizationChecker
            ->expects($this->once())
            ->method('isGranted')
            ->with(null, $menuItemDto)
            ->willReturn(true);

        $token = $this->createMock(TokenInterface::class);
        $result = $this->callVoteOnAttribute(Permission::EA_VIEW_MENU_ITEM, $menuItemDto, $token);

        $this->assertTrue($result);
    }

    public function testVoteOnExecuteActionPermissionGrantsAccessWhenActionIsEnabledAndUserHasPermission(): void
    {
        $crudDto = $this->createCrudDtoWithActionsConfig(
            actionPermissions: ['edit' => 'ROLE_EDITOR'],
            disabledActions: []
        );

        $this->setupAdminContextWithCrud($crudDto);

        $this->authorizationChecker
            ->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_EDITOR', $this->anything())
            ->willReturn(true);

        $token = $this->createMock(TokenInterface::class);
        $subject = ['action' => 'edit', 'entity' => null, 'entityFqcn' => 'App\Entity\Post'];
        $result = $this->callVoteOnAttribute(Permission::EA_EXECUTE_ACTION, $subject, $token);

        $this->assertTrue($result);
    }

    public function testVoteOnExecuteActionPermissionDeniesAccessWhenActionIsDisabled(): void
    {
        $crudDto = $this->createCrudDtoWithActionsConfig(
            actionPermissions: [],
            disabledActions: ['delete']
        );

        $this->setupAdminContextWithCrud($crudDto);

        // isGranted is NOT called because the code uses short-circuit evaluation:
        // the action is disabled, so the check fails before calling isGranted
        $this->authorizationChecker
            ->expects($this->never())
            ->method('isGranted');

        $token = $this->createMock(TokenInterface::class);
        $subject = ['action' => 'delete', 'entity' => null, 'entityFqcn' => 'App\Entity\Post'];
        $result = $this->callVoteOnAttribute(Permission::EA_EXECUTE_ACTION, $subject, $token);

        $this->assertFalse($result);
    }

    public function testVoteOnExecuteActionPermissionDeniesAccessWhenUserLacksPermission(): void
    {
        $crudDto = $this->createCrudDtoWithActionsConfig(
            actionPermissions: ['edit' => 'ROLE_SUPER_ADMIN'],
            disabledActions: []
        );

        $this->setupAdminContextWithCrud($crudDto);

        $this->authorizationChecker
            ->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_SUPER_ADMIN', $this->anything())
            ->willReturn(false);

        $token = $this->createMock(TokenInterface::class);
        $subject = ['action' => 'edit', 'entity' => null, 'entityFqcn' => 'App\Entity\Post'];
        $result = $this->callVoteOnAttribute(Permission::EA_EXECUTE_ACTION, $subject, $token);

        $this->assertFalse($result);
    }

    public function testVoteOnExecuteActionPermissionWithActionDto(): void
    {
        $crudDto = $this->createCrudDtoWithActionsConfig(
            actionPermissions: ['index' => 'ROLE_USER'],
            disabledActions: []
        );

        $this->setupAdminContextWithCrud($crudDto);

        $actionDto = new ActionDto();
        $actionDto->setName('index');

        $this->authorizationChecker
            ->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_USER', $this->anything())
            ->willReturn(true);

        $token = $this->createMock(TokenInterface::class);
        $subject = ['action' => $actionDto, 'entity' => null, 'entityFqcn' => null];
        $result = $this->callVoteOnAttribute(Permission::EA_EXECUTE_ACTION, $subject, $token);

        $this->assertTrue($result);
    }

    public function testVoteOnExecuteActionPermissionUsesEntityInstanceAsSubject(): void
    {
        $crudDto = $this->createCrudDtoWithActionsConfig(
            actionPermissions: ['edit' => 'ROLE_EDITOR'],
            disabledActions: []
        );

        $this->setupAdminContextWithCrud($crudDto);

        $entityInstance = new \stdClass();
        $entityDto = $this->createEntityDtoWithInstance($entityInstance);

        $this->authorizationChecker
            ->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_EDITOR', $entityInstance)
            ->willReturn(true);

        $token = $this->createMock(TokenInterface::class);
        $subject = ['action' => 'edit', 'entity' => $entityDto, 'entityFqcn' => null];
        $result = $this->callVoteOnAttribute(Permission::EA_EXECUTE_ACTION, $subject, $token);

        $this->assertTrue($result);
    }

    public function testVoteOnExecuteActionPermissionWithNoSpecificPermission(): void
    {
        $crudDto = $this->createCrudDtoWithActionsConfig(
            actionPermissions: [],  // no specific permission for any action
            disabledActions: []
        );

        $this->setupAdminContextWithCrud($crudDto);

        $this->authorizationChecker
            ->expects($this->once())
            ->method('isGranted')
            ->with(null, $this->anything())
            ->willReturn(true);

        $token = $this->createMock(TokenInterface::class);
        $subject = ['action' => 'index', 'entity' => null, 'entityFqcn' => 'App\Entity\Post'];
        $result = $this->callVoteOnAttribute(Permission::EA_EXECUTE_ACTION, $subject, $token);

        $this->assertTrue($result);
    }

    public function testVoteOnViewPropertyPermissionGrantsAccessWhenUserHasPermission(): void
    {
        $fieldDto = new FieldDto();
        $fieldDto->setPermission('ROLE_ADMIN');

        $this->authorizationChecker
            ->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_ADMIN', $fieldDto)
            ->willReturn(true);

        $token = $this->createMock(TokenInterface::class);
        $result = $this->callVoteOnAttribute(Permission::EA_VIEW_FIELD, $fieldDto, $token);

        $this->assertTrue($result);
    }

    public function testVoteOnViewPropertyPermissionDeniesAccessWhenUserLacksPermission(): void
    {
        $fieldDto = new FieldDto();
        $fieldDto->setPermission('ROLE_SUPER_ADMIN');

        $this->authorizationChecker
            ->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_SUPER_ADMIN', $fieldDto)
            ->willReturn(false);

        $token = $this->createMock(TokenInterface::class);
        $result = $this->callVoteOnAttribute(Permission::EA_VIEW_FIELD, $fieldDto, $token);

        $this->assertFalse($result);
    }

    public function testVoteOnViewEntityPermissionGrantsAccessWhenUserHasPermission(): void
    {
        $entityInstance = new \stdClass();
        $entityDto = $this->createEntityDtoWithInstance($entityInstance, 'ROLE_VIEWER');

        $this->authorizationChecker
            ->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_VIEWER', $entityInstance)
            ->willReturn(true);

        $token = $this->createMock(TokenInterface::class);
        $result = $this->callVoteOnAttribute(Permission::EA_ACCESS_ENTITY, $entityDto, $token);

        $this->assertTrue($result);
    }

    public function testVoteOnViewEntityPermissionDeniesAccessWhenUserLacksPermission(): void
    {
        $entityInstance = new \stdClass();
        $entityDto = $this->createEntityDtoWithInstance($entityInstance, 'ROLE_SUPER_ADMIN');

        $this->authorizationChecker
            ->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_SUPER_ADMIN', $entityInstance)
            ->willReturn(false);

        $token = $this->createMock(TokenInterface::class);
        $result = $this->callVoteOnAttribute(Permission::EA_ACCESS_ENTITY, $entityDto, $token);

        $this->assertFalse($result);
    }

    public function testVoteOnExitImpersonationPermissionGrantsAccessWhenUserIsImpersonating(): void
    {
        $this->authorizationChecker
            ->expects($this->once())
            ->method('isGranted')
            ->with($this->logicalOr('IS_IMPERSONATOR', 'ROLE_PREVIOUS_ADMIN'))
            ->willReturn(true);

        $token = $this->createMock(TokenInterface::class);
        $result = $this->callVoteOnAttribute(Permission::EA_EXIT_IMPERSONATION, null, $token);

        $this->assertTrue($result);
    }

    public function testVoteOnExitImpersonationPermissionDeniesAccessWhenUserIsNotImpersonating(): void
    {
        $this->authorizationChecker
            ->expects($this->once())
            ->method('isGranted')
            ->with($this->logicalOr('IS_IMPERSONATOR', 'ROLE_PREVIOUS_ADMIN'))
            ->willReturn(false);

        $token = $this->createMock(TokenInterface::class);
        $result = $this->callVoteOnAttribute(Permission::EA_EXIT_IMPERSONATION, null, $token);

        $this->assertFalse($result);
    }

    public function testVoteOnViewFieldPermissionWithNullPermission(): void
    {
        $fieldDto = new FieldDto();
        // no permission set (null by default)

        $this->authorizationChecker
            ->expects($this->once())
            ->method('isGranted')
            ->with(null, $fieldDto)
            ->willReturn(true);

        $token = $this->createMock(TokenInterface::class);
        $result = $this->callVoteOnAttribute(Permission::EA_VIEW_FIELD, $fieldDto, $token);

        $this->assertTrue($result);
    }

    /**
     * Helper method to call the protected voteOnAttribute method.
     */
    private function callVoteOnAttribute(string $permissionName, mixed $subject, TokenInterface $token): bool
    {
        $reflection = new \ReflectionMethod(SecurityVoter::class, 'voteOnAttribute');

        return $reflection->invoke($this->voter, $permissionName, $subject, $token);
    }

    private function createCrudDtoWithActionsConfig(array $actionPermissions, array $disabledActions): CrudDto
    {
        $actionConfigDto = new ActionConfigDto();
        $actionConfigDto->setActionPermissions($actionPermissions);
        $actionConfigDto->disableActions($disabledActions);

        $crudDto = new CrudDto();
        $crudDto->setActionsConfig($actionConfigDto);

        return $crudDto;
    }

    private function setupAdminContextWithCrud(CrudDto $crudDto): void
    {
        $adminContext = AdminContext::forTesting(
            crudContext: CrudContext::forTesting(crudDto: $crudDto),
        );

        $request = new Request();
        $request->attributes->set(EA::CONTEXT_REQUEST_ATTRIBUTE, $adminContext);
        $this->requestStack->push($request);
    }

    private function createEntityDtoWithInstance(object $instance, ?string $permission = null): EntityDto
    {
        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->method('getSingleIdentifierFieldName')->willReturn('id');

        return new EntityDto(\stdClass::class, $classMetadata, $permission, $instance);
    }
}
