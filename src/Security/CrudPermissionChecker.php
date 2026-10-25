<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Security;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Context\AdminContextInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Security\CrudPermissionCheckerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\CrudDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Factory\AdminContextFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\ControllerFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Service\ResetInterface;

final class CrudPermissionChecker implements CrudPermissionCheckerInterface, ResetInterface
{
    /** @var array<string, CrudDto> */
    private array $targetCrudDtoCache = [];

    public function __construct(
        private readonly ControllerFactory $controllerFactory,
        private readonly AdminContextFactory $adminContextFactory,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
    ) {
    }

    public function isGranted(AdminContextInterface $context, string $crudControllerFqcn, string $action, ?EntityDto $entityDto = null): bool
    {
        $targetCrud = $this->getTargetCrudDto($context, $crudControllerFqcn, $action);

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
     *
     * @param AdminContextInterface<object> $context
     */
    private function getTargetCrudDto(AdminContextInterface $context, string $crudControllerFqcn, string $crudAction): CrudDto
    {
        $dashboardControllerFqcn = $context->getDashboardControllerFqcn();
        $key = $dashboardControllerFqcn.'::'.$crudControllerFqcn.'::'.$crudAction;
        if (\array_key_exists($key, $this->targetCrudDtoCache)) {
            return $this->targetCrudDtoCache[$key];
        }

        // a fresh Request on purpose: the EA attributes of the current one (entity id, filters, sort)
        // would otherwise leak into the target controller's context
        $request = new Request();
        $request->setLocale($context->getRequest()->getLocale());

        try {
            $dashboardController = $this->controllerFactory->getDashboardControllerInstance($dashboardControllerFqcn, $request);
        } catch (NotFoundHttpException $e) {
            throw new \LogicException(sprintf('The "%s" dashboard controller of the given admin context cannot be resolved.', $dashboardControllerFqcn), previous: $e);
        }

        if (null === $dashboardController) {
            throw new \LogicException(sprintf('The "%s" dashboard controller of the given admin context cannot be resolved.', $dashboardControllerFqcn));
        }

        // resolved through the 'index' method because custom action names don't always match a method name
        try {
            $crudController = $this->controllerFactory->getCrudControllerInstance($crudControllerFqcn, Action::INDEX, $request);
        } catch (NotFoundHttpException $e) {
            throw new \InvalidArgumentException(sprintf('The "%s" class is not a CRUD controller or it doesn\'t exist.', $crudControllerFqcn), previous: $e);
        }

        if (null === $crudController) {
            throw new \InvalidArgumentException(sprintf('The "%s" class is not a CRUD controller or it doesn\'t exist.', $crudControllerFqcn));
        }

        $targetContext = $this->adminContextFactory->create(
            $request,
            $dashboardController,
            $crudController,
            $crudAction,
        );

        return $this->targetCrudDtoCache[$key] = $targetContext->getCrud()
            ?? throw new \LogicException(sprintf('The admin context of the "%s" CRUD controller has no CRUD configuration.', $crudControllerFqcn));
    }
}
