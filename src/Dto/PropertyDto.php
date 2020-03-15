<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

use Symfony\Component\HttpFoundation\ParameterBag;

final class PropertyDto
{
    private $type;
    private $name;
    private $value;
    private $formattedValue;
    private $label;
    private $formType;
    private $formTypeOptions;
    private $sortable;
    private $virtual;
    private $permission;
    private $textAlign;
    private $help;
    private $cssClass;
    private $translationParameters;
    private $templateName;
    private $templatePath;
    private $assets;
    private $customOptions;
    private $doctrineMetadata;

    public function __construct(string $type, string $name, $value, $formattedValue, ?string $formType, array $formTypeOptions, ?bool $sortable, ?bool $virtual, ?string $label, ?string $permission, ?string $textAlign, ?string $help, ?string $cssClass, array $translationParameters, ?string $templateName, ?string $templatePath, AssetsDto $assetDto, ParameterBag $customOptions, ParameterBag $doctrineMetadata)
    {
        $this->type = $type;
        $this->name = $name;
        $this->value = $value;
        $this->formattedValue = $formattedValue;
        $this->formType = $formType;
        $this->formTypeOptions = $formTypeOptions;
        $this->sortable = $sortable;
        $this->virtual = $virtual;
        $this->label = $label;
        $this->permission = $permission;
        $this->textAlign = $textAlign;
        $this->help = $help;
        $this->cssClass = $cssClass;
        $this->translationParameters = $translationParameters;
        $this->templateName = $templateName;
        $this->templatePath = $templatePath;
        $this->assets = $assetDto;
        $this->customOptions = $customOptions;
        $this->doctrineMetadata = $doctrineMetadata;
    }

    public function getUniqueId(): string
    {
        return spl_object_hash($this);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Returns the original unmodified value stored in the entity property.
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Returns the value to be displayed for the entity property (it could be the
     * same as the value stored in the property or not).
     */
    public function getFormattedValue()
    {
        return $this->formattedValue;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function getFormType(): ?string
    {
        return $this->formType;
    }

    public function getFormTypeOptions(): array
    {
        return $this->formTypeOptions;
    }

    public function isSortable(): ?bool
    {
        return $this->sortable;
    }

    public function isVirtual(): bool
    {
        return $this->virtual;
    }

    public function getTextAlign(): ?string
    {
        return $this->textAlign;
    }

    public function getPermission(): ?string
    {
        return $this->permission;
    }

    public function getHelp(): ?string
    {
        return $this->help;
    }

    public function getCssClass(): ?string
    {
        return $this->cssClass;
    }

    public function getTranslationParameters(): array
    {
        return $this->translationParameters;
    }

    public function getTemplatePath(): string
    {
        return $this->templatePath;
    }

    public function getAssets(): AssetsDto
    {
        return $this->assets;
    }

    public function getCustomOptions(): ParameterBag
    {
        return $this->customOptions;
    }

    public function getDoctrineMetadata(): ParameterBag
    {
        return $this->doctrineMetadata;
    }
}
