<?php

namespace EasyCorp\Bundle\EasyAdminBundle\ArgumentResolver;

use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class AdminContextResolver implements ArgumentValueResolverInterface
{
    private $adminContextProvider;

    public function __construct(AdminContextProvider $adminContextProvider)
    {
        $this->adminContextProvider = $adminContextProvider;
    }

    /**
     * @return bool
     */
    public function supports(Request $request, ArgumentMetadata $argument)
    {
        return AdminContext::class === $argument->getType();
    }

    /**
     * @return iterable
     */
    public function resolve(Request $request, ArgumentMetadata $argument)
    {
        yield $this->adminContextProvider->getContext();
    }
}
