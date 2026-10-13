<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Provider;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Provider\AdminContextProviderInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\ComponentDefaultsDto;

/**
 * Gives Twig components the default options configured for the current request,
 * so they don't depend on the whole admin context.
 *
 * @internal
 */
final class ComponentDefaultsProvider
{
    public function __construct(
        private readonly AdminContextProviderInterface $adminContextProvider,
    ) {
    }

    public function getDefaults(): ComponentDefaultsDto
    {
        $context = $this->adminContextProvider->getContext();
        if (null === $context) {
            return new ComponentDefaultsDto();
        }

        if (null !== $crudDto = $context->getCrud()) {
            return $crudDto->getComponentDefaults();
        }

        // pages without a CRUD controller (e.g. the dashboard or custom pages) use the
        // defaults configured in the configureCrud() method of the dashboard; the method
        // is not part of AdminContextInterface yet to not break other implementations
        if (method_exists($context, 'getDashboardComponentDefaults')) {
            return $context->getDashboardComponentDefaults();
        }

        return new ComponentDefaultsDto();
    }
}
