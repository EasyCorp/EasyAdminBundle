<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

final class FilterDto
{
    private $formType;
    private $property;
    private $label;

    public function __construct(string $formType, string $property, $label)
    {
        $this->formType = $formType;
        $this->property = $property;
        $this->label = $label;
    }

    public function getFormType(): string
    {
        return $this->formType;
    }

    public function getProperty(): string
    {
        return $this->property;
    }

    public function getLabel()
    {
        return $this->label;
    }
}
