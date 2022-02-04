<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Event;

use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class AfterCrudActionEvent
{
    use StoppableEventTrait;

    private ?AdminContext $adminContext;
    private KeyValueStore $responseParameters;

    public function __construct(?AdminContext $adminContext, KeyValueStore $responseParameters)
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
        $this->responseParameters->setAll($parameters);
    }

    public function getResponseParameters(): KeyValueStore
    {
        return $this->responseParameters;
    }
}
