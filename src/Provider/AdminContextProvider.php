<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Provider;

use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Context\AdminContextInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Provider\AdminContextProviderInterface;
use Symfony\Component\HttpFoundation\RequestStack;

final readonly class AdminContextProvider implements AdminContextProviderInterface
{
    public function __construct(private RequestStack $requestStack)
    {
    }

    public function getContext(): ?AdminContextInterface
    {
        return $this->requestStack->getCurrentRequest()?->attributes->get(EA::CONTEXT_REQUEST_ATTRIBUTE);
    }
}
