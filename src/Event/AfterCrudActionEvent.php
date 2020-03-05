<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Event;

use EasyCorp\Bundle\EasyAdminBundle\Context\ApplicationContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\ResponseParameters;

final class AfterCrudActionEvent
{
    use StoppableEventTrait;

    private $applicationContext;
    private $responseParameters;

    public function __construct(?ApplicationContext $applicationContext, ResponseParameters $responseParameters)
    {
        $this->applicationContext = $applicationContext;
        $this->responseParameters = $responseParameters;
    }

    public function getApplicationContext(): ?ApplicationContext
    {
        return $this->applicationContext;
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
