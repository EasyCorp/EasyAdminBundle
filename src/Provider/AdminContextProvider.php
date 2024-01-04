<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Provider;

use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class AdminContextProvider implements AdminContextProviderInterface
{
    public function __construct(
        private RequestStack $requestStack
    ) {}

    public function getContext(): ?AdminContext
    {
        return $this->requestStack->getCurrentRequest()?->get(EA::CONTEXT_REQUEST_ATTRIBUTE);
    }
}
