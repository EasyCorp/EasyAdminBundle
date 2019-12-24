<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Configuration\Property;

use EasyCorp\Bundle\EasyAdminBundle\Dto\AssetDto;
use Symfony\Component\HttpFoundation\ParameterBag;

trait PropertyConfigGettersTrait
{
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

    public function getTranslationParams(): array
    {
        return $this->translationParams;
    }

    public function getConfiguredTemplateName(): ?string
    {
        return $this->templateName;
    }

    public function getConfiguredTemplatePath(): ?string
    {
        return $this->templatePath;
    }

    /**
     * This is the template used to render the field contents. It's resolved at
     * runtime and it considers all possible field values (null, not readable, etc.).
     */
    public function getTemplatePath(): string
    {
        return $this->resolvedTemplatePath;
    }

    public function getAssets(): AssetDto
    {
        return $this->assets;
    }

    public function getCustomOptions(): ParameterBag
    {
        return new ParameterBag($this->customOptions);
    }
}
