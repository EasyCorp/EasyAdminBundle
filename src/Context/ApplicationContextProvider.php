<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Context;

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * This is used to inject it in services and controllers to get the current
 * application context variable.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class ApplicationContextProvider
{
    private $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function getContext(): ?ApplicationContext
    {
        $currentRequest = $this->requestStack->getCurrentRequest();

        return null !== $currentRequest ? $currentRequest->get(ApplicationContext::ATTRIBUTE_KEY) : null;
    }
}
