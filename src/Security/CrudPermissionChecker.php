<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Security;

use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Security\CrudPermissionCheckerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\CrudDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Factory\AdminContextFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\ControllerFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Service\ResetInterface;

final class CrudPermissionChecker implements CrudPermissionCheckerInterface, ResetInterface
{
    /** @var array<string, ?CrudDto> */
    private array $targetCrudDtoCache = [];

    public function __construct(
        private readonly ControllerFactory $controllerFactory,
        private readonly AdminContextFactory $adminContextFactory,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
    ) {
    }

    public function isGranted(AdminContext $context, string $crudControllerFqcn, string $action, ?EntityDto $entityDto = null): bool
    {
        $targetCrud = $this->getTargetCrudDto($context, $crudControllerFqcn, $action);
        if (null === $targetCrud) {
            return true;
        }

        $entityPermission = $targetCrud->getEntityPermission();
        $entityInstance = $entityDto?->getInstance();
        if (null !== $entityPermission && null !== $entityInstance
            && !$this->authorizationChecker->isGranted($entityPermission, $entityInstance)) {
            return false;
        }

        return $this->authorizationChecker->isGranted(
            Permission::EA_EXECUTE_ACTION,
            ['crud' => $targetCrud, 'action' => $action, 'entity' => $entityDto],
        );
    }

    public function reset(): void
    {
        $this->targetCrudDtoCache = [];
    }

    /**
     * The CrudDto is cached because building the target AdminContext once per row
     * (e.g. AssociationField links on an index page) is too expensive.
     */
    private function getTargetCrudDto(AdminContext $context, string $crudControllerFqcn, string $crudAction): ?CrudDto
    {
        $key = $crudControllerFqcn.'::'.$crudAction;
        if (\array_key_exists($key, $this->targetCrudDtoCache)) {
            return $this->targetCrudDtoCache[$key];
        }

        // a fresh Request on purpose: the EA attributes of the current one (entity id, filters, sort)
        // would otherwise leak into the target controller's context
        $request = new Request();
        $request->setLocale($context->getRequest()->getLocale());

        $dashboardController = $this->controllerFactory->getDashboardControllerInstance(
            $context->getDashboardControllerFqcn(),
            $request,
        );

        $crudController = $this->controllerFactory->getCrudControllerInstance(
            $crudControllerFqcn,
            $crudAction,
            $request,
        );

        if (null === $crudController || null === $dashboardController) {
            return $this->targetCrudDtoCache[$key] = null;
        }

        $targetContext = $this->adminContextFactory->create(
            $request,
            $dashboardController,
            $crudController,
            $crudAction,
        );

        return $this->targetCrudDtoCache[$key] = $targetContext->getCrud();
    }
}
