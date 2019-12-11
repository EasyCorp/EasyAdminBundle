<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

use EasyCorp\Bundle\EasyAdminBundle\Collection\TemplateDtoCollection;

final class CrudDto
{
    private $entityFqcn;
    private $labelInSingular;
    private $labelInPlural;
    private $dateFormat;
    private $timeFormat ;
    private $dateTimeFormat;
    private $dateIntervalFormat;
    private $numberFormat;
    private $customTemplates;
    private $formThemes;

    public function __construct(?string $entityFqcn, string $labelInSingular, string $labelInPlural, string $dateFormat, string $timeFormat, string $dateTimeFormat, string $dateIntervalFormat, ?string $numberFormat, TemplateDtoCollection $customTemplates, $formThemes)
    {
        $this->entityFqcn = $entityFqcn;
        $this->labelInSingular = $labelInSingular;
        $this->labelInPlural = $labelInPlural;
        $this->dateFormat = $dateFormat;
        $this->timeFormat = $timeFormat;
        $this->dateTimeFormat = $dateTimeFormat;
        $this->dateIntervalFormat = $dateIntervalFormat;
        $this->numberFormat = $numberFormat;
        $this->customTemplates = $customTemplates;
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

    public function getDateFormat(): string
    {
        return $this->dateFormat;
    }

    public function getTimeFormat(): string
    {
        return $this->timeFormat;
    }

    public function getDateTimeFormat(): string
    {
        return $this->dateTimeFormat;
    }

    public function getDateIntervalFormat(): string
    {
        return $this->dateIntervalFormat;
    }

    public function getNumberFormat(): ?string
    {
        return $this->numberFormat;
    }

    public function getCustomTemplates(): TemplateDtoCollection
    {
        return $this->customTemplates;
    }

    public function getFormThemes(): array
    {
        return $this->formThemes;
    }
}
