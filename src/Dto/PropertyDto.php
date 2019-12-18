<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

use Symfony\Component\HttpFoundation\ParameterBag;

final class PropertyDto
{
    use PropertyAccessorTrait;
    use PropertyModifierTrait;

    private $name;
    private $type;
    private $value;
    private $formattedValue;
    private $formType;
    private $formTypeOptions = [];
    private $sortable;
    private $virtual;
    private $label;
    private $permission;
    private $textAlign;
    private $help;
    private $cssClass;
    private $translationParams;
    /** @internal */
    private $templateName;
    /** @internal */
    private $templatePath;
    private $resolvedTemplatePath;
    private $customTemplateParams = [];
    private $assets = [];
    private $customOptions;

    public function __construct(string $name, string $type, string $formType, array $formTypeOptions, ?bool $sortable, ?string $label, ?string $permission, string $textAlign, ?string $help, ?string $cssClass, array $translationParams, ?string $templateName, ?string $templatePath, array $customTemplateParams, array $assets, array $customOptions)
    {
        $this->name = $name;
        $this->type = $type;
        $this->formType = $formType;
        $this->formTypeOptions = $formTypeOptions;
        $this->sortable = $sortable;
        $this->label = $label;
        $this->permission = $permission;
        $this->textAlign = $textAlign;
        $this->help = $help;
        $this->cssClass = $cssClass;
        $this->translationParams = $translationParams;
        $this->templateName = $templateName;
        $this->templatePath = $templatePath;
        $this->customTemplateParams = $customTemplateParams;
        $this->assets = $assets;
        $this->customOptions = $customOptions;
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

    public function getFormType(): string
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

    public function getTranslationParams(): array
    {
        return $this->translationParams;
    }

    /**
     * This is the template used to render the field contents. It's resolved at
     * runtime and it considers all possible field values (null, not readable, etc.).
     */
    public function getTemplatePath(): string
    {
        return $this->resolvedTemplatePath;
    }

    public function getCustomTemplateParams(): array
    {
        return $this->customTemplateParams;
    }

    public function getAssets(): AssetDto
    {
        return $this->assets->getAsDto();
    }

    public function getCustomOptions(): ParameterBag
    {
        return new ParameterBag($this->customOptions);
    }
}
