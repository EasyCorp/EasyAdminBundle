<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

use EasyCorp\Bundle\EasyAdminBundle\Collection\TemplateDtoCollection;

final class CrudDto
{
    use PropertyAccessorTrait;
    use PropertyModifierTrait;

    private $entityFqcn;
    private $labelInSingular;
    private $labelInPlural;
    private $dateFormat;
    private $timeFormat;
    private $dateTimePattern;
    private $dateIntervalFormat;
    private $numberFormat;
    private $overriddenTemplates;
    private $formThemes;
    /** @var CrudPageDto */
    private $crudPageDto;
    private $actionName;

    public function __construct(?string $entityFqcn, string $labelInSingular, string $labelInPlural, ?string $dateFormat, ?string $timeFormat, string $dateTimePattern, string $dateIntervalFormat, ?string $numberFormat, TemplateDtoCollection $overriddenTemplates, $formThemes)
    {
        $this->entityFqcn = $entityFqcn;
        $this->labelInSingular = $labelInSingular;
        $this->labelInPlural = $labelInPlural;
        $this->dateFormat = $dateFormat;
        $this->timeFormat = $timeFormat;
        $this->dateTimePattern = $dateTimePattern;
        $this->dateIntervalFormat = $dateIntervalFormat;
        $this->numberFormat = $numberFormat;
        $this->overriddenTemplates = $overriddenTemplates;
        $this->formThemes = $formThemes;
    }

    public function getEntityFqcn(): string
    {
        return $this->entityFqcn;
    }

    public function getLabelInSingular(): string
    {
        return $this->labelInSingular;
    }

    public function getLabelInPlural(): string
    {
        return $this->labelInPlural;
    }

    public function getDateFormat(): ?string
    {
        return $this->dateFormat;
    }

    public function getTimeFormat(): ?string
    {
        return $this->timeFormat;
    }

    public function getDateTimePattern(): string
    {
        return $this->dateTimePattern;
    }

    public function getDateIntervalFormat(): string
    {
        return $this->dateIntervalFormat;
    }

    public function getNumberFormat(): ?string
    {
        return $this->numberFormat;
    }

    public function getFormThemes(): array
    {
        return $this->formThemes;
    }

    public function getPage(): ?CrudPageDto
    {
        return $this->crudPageDto;
    }

    public function getAction(): string
    {
        return $this->actionName;
    }
}
