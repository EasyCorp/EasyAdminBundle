<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

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
    private $defaultTemplates;
    private $formThemes;

    public function __construct(string $entityFqcn, string $labelInSingular, string $labelInPlural, string $dateFormat, string $timeFormat, string $dateTimeFormat, string $dateIntervalFormat, ?string $numberFormat, array $customTemplates, array $defaultTemplates, $formThemes)
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
        $this->defaultTemplates = $defaultTemplates;
        $this->formThemes = $formThemes;
    }

    public function getEntityClass(): string
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

    public function getCustomTemplate(string $templateName = null): ?string
    {
        return $this->customTemplates[$templateName] ?? null;
    }

    public function getDefaultTemplate(string $templateName = null): ?string
    {
        return $this->defaultTemplates[$templateName] ?? null;
    }

    public function getFormThemes(): array
    {
        return $this->formThemes;
    }
}
