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

        // entity-level permission (Crud::setEntityPermission() on the target controller)
        $entityPermission = $targetCrud->getEntityPermission();
        $entityInstance = $entityDto?->getInstance();
        if (null !== $entityPermission && null !== $entityInstance
            && !$this->authorizationChecker->isGranted($entityPermission, $entityInstance)) {
            return false;
        }

        // action-level permission (Actions::setPermission() on the target controller)
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
     * Resolves and caches the target CRUD controller's CrudDto so callers can run permission
     * checks against it without rebuilding the full AdminContext once per row (e.g. AssociationField
     * links on an index page).
     */
    private function getTargetCrudDto(AdminContext $sourceContext, string $crudControllerFqcn, string $crudAction): ?CrudDto
    {
        $key = $crudControllerFqcn.'::'.$crudAction;
        if (\array_key_exists($key, $this->targetCrudDtoCache)) {
            return $this->targetCrudDtoCache[$key];
        }

        // a fresh Request is used on purpose: EA-specific attributes from the main request
        // (entity id, filters, sort, etc.) would otherwise leak into the target controller's context.
        // the voter only consumes action permissions and disabled actions from the resulting CrudDto.
        // the locale is copied so the target context's translated entity labels match the source page.
        $request = new Request();
        $request->setLocale($sourceContext->getRequest()->getLocale());

        $dashboardController = $this->controllerFactory->getDashboardControllerInstance(
            $sourceContext->getDashboardControllerFqcn(),
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
