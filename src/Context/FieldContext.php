<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Context;

final class FieldContext
{
    private string $crudControllerFqcn;
    private string $crudControllerAction;
    private string $crudControllerPageName;

    public function __construct(
        string $crudControllerFqcn,
        string $crudControllerAction,
        string $crudControllerPageName,
    ) {
        $this->crudControllerFqcn = $crudControllerFqcn;
        $this->crudControllerAction = $crudControllerAction;
        $this->crudControllerPageName = $crudControllerPageName;
    }

    public function getCrudControllerFqcn(): string
    {
        return $this->crudControllerFqcn;
    }

    public function getCrudControllerAction(): string
    {
        return $this->crudControllerAction;
    }

    public function getCrudControllerPageName(): string
    {
        return $this->crudControllerPageName;
    }
}
