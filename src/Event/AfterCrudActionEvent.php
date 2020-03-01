<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Event;

use EasyCorp\Bundle\EasyAdminBundle\Context\ApplicationContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\ResponseParams;

final class AfterCrudActionEvent
{
    use StoppableEventTrait;

    private $applicationContext;
    private $responseParams;

    public function __construct(?ApplicationContext $applicationContext, ResponseParams $responseParams)
    {
        $this->applicationContext = $applicationContext;
        $this->responseParams = $responseParams;
    }

    public function getApplicationContext(): ?ApplicationContext
    {
        return $this->applicationContext;
    }

    /**
     * Use this method to pass additional parameters to the rendered template
     * Format: ['paramName' => $paramValue, ...].
     */
    public function addTemplateParameters(array $parameters): void
    {
        $this->responseParams = array_merge($this->responseParams, $parameters);
    }

    public function getResponseParams(): ResponseParams
    {
        return $this->responseParams;
    }
}
