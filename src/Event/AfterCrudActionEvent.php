<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Event;

use EasyCorp\Bundle\EasyAdminBundle\Context\ApplicationContext;

final class AfterCrudActionEvent extends StoppableEvent
{
    private $applicationContext;
    private $templateParameters;

    public function __construct(?ApplicationContext $applicationContext, array $templateParameters)
    {
        $this->templateParameters = $templateParameters;
    }

    public function getApplicationContext(): ?ApplicationContext
    {
        return $this->applicationContext;
    }

    /**
     * Use this method to pass additional parameters to the rendered template
     * Format: ['paramName' => $paramValue, ...]
     */
    public function addTemplateParameters(array $parameters): void
    {
        $this->templateParameters = array_merge($this->templateParameters, $parameters);
    }

    public function getTemplateParameters(): array
    {
        return $this->templateParameters;
    }
}
