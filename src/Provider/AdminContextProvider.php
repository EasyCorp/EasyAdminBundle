<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Provider;

use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use Symfony\Component\HttpFoundation\RequestStack;

final class AdminContextProvider implements AdminContextProviderInterface
{
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function getContext(): ?AdminContext
    {
        $currentRequest = $this->requestStack->getCurrentRequest();

        return null !== $currentRequest ? $currentRequest->get(EA::CONTEXT_REQUEST_ATTRIBUTE) : null;
    }
}
