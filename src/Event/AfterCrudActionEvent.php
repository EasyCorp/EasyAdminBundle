<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Event;

use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\ResponseParameters;

final class AfterCrudActionEvent
{
    use StoppableEventTrait;

    private $adminContext;
    private $responseParameters;

    public function __construct(?AdminContext $adminContext, ResponseParameters $responseParameters)
    {
        $this->adminContext = $adminContext;
        $this->responseParameters = $responseParameters;
    }

    public function getAdminContext(): ?AdminContext
    {
        return $this->adminContext;
    }

    /**
     * Use this method to pass additional parameters to the rendered template
     * Format: ['paramName' => $paramValue, ...].
     */
    public function addResponseParameters(array $parameters): void
    {
        $this->responseParameters = array_merge($this->responseParameters, $parameters);
    }

    public function getResponseParameters(): ResponseParameters
    {
        return $this->responseParameters;
    }
}
