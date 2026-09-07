<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Security;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\CrudContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\DashboardContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\I18nContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\RequestContext;
use EasyCorp\Bundle\EasyAdminBundle\Dto\CrudDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Factory\AdminContextFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\ControllerFactory;
use EasyCorp\Bundle\EasyAdminBundle\Security\CrudPermissionChecker;
use EasyCorp\Bundle\EasyAdminBundle\Security\Permission;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\ProjectDomain\DeveloperCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\ProjectDomain\Developer;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class CrudPermissionCheckerTest extends KernelTestCase
{
    public function testGrantsWhenTargetCrudCannotBeResolved(): void
    {
        $authChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authChecker->expects($this->never())->method('isGranted');

        $checker = $this->buildChecker($authChecker);
        $this->primeTargetCrudDtoCache($checker, DeveloperCrudController::class, Action::DETAIL, null);

        $this->assertTrue($checker->isGranted($this->buildContext(), DeveloperCrudController::class, Action::DETAIL, $this->buildDeveloperDto()));
    }

    public function testDeniesWhenTargetEntityPermissionDenies(): void
    {
        $targetCrud = new CrudDto();
        $targetCrud->setEntityPermission('ROLE_DENIED');

        $authChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authChecker->method('isGranted')->willReturnCallback(
            static fn ($attribute) => 'ROLE_DENIED' !== $attribute,
        );

        $checker = $this->buildChecker($authChecker);
        $this->primeTargetCrudDtoCache($checker, DeveloperCrudController::class, Action::DETAIL, $targetCrud);

        $this->assertFalse($checker->isGranted($this->buildContext(), DeveloperCrudController::class, Action::DETAIL, $this->buildDeveloperDto()));
    }

    public function testSkipsEntityPermissionWithoutEntityInstance(): void
    {
        $targetCrud = new CrudDto();
        $targetCrud->setEntityPermission('ROLE_DENIED');

        $authChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authChecker->expects($this->once())->method('isGranted')
            ->with(Permission::EA_EXECUTE_ACTION, ['crud' => $targetCrud, 'action' => Action::INDEX, 'entity' => null])
            ->willReturn(true);

        $checker = $this->buildChecker($authChecker);
        $this->primeTargetCrudDtoCache($checker, DeveloperCrudController::class, Action::INDEX, $targetCrud);

        $this->assertTrue($checker->isGranted($this->buildContext(), DeveloperCrudController::class, Action::INDEX));
    }

    public function testDelegatesActionPermissionToTheVoterWithTheTargetCrud(): void
    {
        $targetCrud = new CrudDto();
        $entityDto = $this->buildDeveloperDto();

        $authChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authChecker->expects($this->once())->method('isGranted')
            ->with(Permission::EA_EXECUTE_ACTION, ['crud' => $targetCrud, 'action' => Action::DETAIL, 'entity' => $entityDto])
            ->willReturn(false);

        $checker = $this->buildChecker($authChecker);
        $this->primeTargetCrudDtoCache($checker, DeveloperCrudController::class, Action::DETAIL, $targetCrud);

        $this->assertFalse($checker->isGranted($this->buildContext(), DeveloperCrudController::class, Action::DETAIL, $entityDto));
    }

    public function testResolvesTheTargetCrudFromTheRealControllerAndCachesIt(): void
    {
        $authChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authChecker->method('isGranted')->willReturn(true);

        $checker = $this->buildChecker($authChecker);
        $checker->isGranted($this->buildContext(), DeveloperCrudController::class, Action::DETAIL, $this->buildDeveloperDto());

        $cache = (new \ReflectionProperty($checker, 'targetCrudDtoCache'))->getValue($checker);

        $this->assertArrayHasKey(DeveloperCrudController::class.'::'.Action::DETAIL, $cache);
        $this->assertInstanceOf(CrudDto::class, $cache[DeveloperCrudController::class.'::'.Action::DETAIL]);
        $this->assertSame(DeveloperCrudController::class, $cache[DeveloperCrudController::class.'::'.Action::DETAIL]->getControllerFqcn());

        $checker->reset();

        $this->assertSame([], (new \ReflectionProperty($checker, 'targetCrudDtoCache'))->getValue($checker));
    }

    private function buildChecker(AuthorizationCheckerInterface $authChecker): CrudPermissionChecker
    {
        self::bootKernel();

        return new CrudPermissionChecker(
            static::getContainer()->get(ControllerFactory::class),
            static::getContainer()->get(AdminContextFactory::class),
            $authChecker,
        );
    }

    private function buildContext(): AdminContext
    {
        $request = new Request();
        $request->setLocale('en');

        return AdminContext::forTesting(
            requestContext: RequestContext::forTesting($request),
            crudContext: CrudContext::forTesting(new CrudDto()),
            dashboardContext: DashboardContext::forTesting(dashboardControllerFqcn: DashboardController::class),
            i18nContext: I18nContext::forTesting('en'),
        );
    }

    private function buildDeveloperDto(): EntityDto
    {
        $reflectedClass = new \ReflectionClass(EntityDto::class);
        $entityDto = $reflectedClass->newInstanceWithoutConstructor();
        $reflectedClass->getProperty('entityInstance')->setValue($entityDto, new Developer());

        return $entityDto;
    }

    /**
     * Seeds the target-CrudDto cache so the permission gates run against a controlled CrudDto
     * without exercising the full AdminContext-resolution chain.
     */
    private function primeTargetCrudDtoCache(CrudPermissionChecker $checker, string $crudControllerFqcn, string $crudAction, ?CrudDto $crudDto): void
    {
        $property = new \ReflectionProperty($checker, 'targetCrudDtoCache');
        $cache = $property->getValue($checker);
        $cache[$crudControllerFqcn.'::'.$crudAction] = $crudDto;
        $property->setValue($checker, $cache);
    }
}
