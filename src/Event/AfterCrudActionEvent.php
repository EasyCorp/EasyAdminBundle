<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Event;

use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStoreInterface;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContextInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class AfterCrudActionEvent
{
    use StoppableEventTrait;

    private ?AdminContextInterface $adminContext;

    private KeyValueStoreInterface $responseParameters;

    public function __construct(
        ?AdminContextInterface $adminContext,
        KeyValueStoreInterface $responseParameters
    ) {
        $this->adminContext = $adminContext;
        $this->responseParameters = $responseParameters;
    }

    public function getAdminContext(): ?AdminContextInterface
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

    public function getResponseParameters(): KeyValueStoreInterface
    {
        return $this->responseParameters;
    }
}
