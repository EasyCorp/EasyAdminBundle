<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

use EasyCorp\Bundle\EasyAdminBundle\Configuration\Property\PropertyConfigGettersTrait;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\Property\PropertyConfigPropertiesTrait;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\Property\PropertyConfigSettersTrait;

/**
 * This is the only DTO class which includes both getters and setters because the
 * properties of this object are processed in multiple steps and it's crucial to
 * have quick and easy access to get and set any properties.
 */
final class PropertyDto
{
    use PropertyConfigPropertiesTrait;
    use PropertyConfigGettersTrait;
    use PropertyConfigSettersTrait;

    public function __construct(string $name, string $type, ?string $formType, array $formTypeOptions, ?bool $sortable, ?string $label, ?string $permission, string $textAlign, ?string $help, ?string $cssClass, array $translationParams, ?string $templateName, ?string $templatePath, AssetDto $assetDto, array $customOptions)
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
        $this->assets = $assetDto;
        $this->customOptions = $customOptions;
    }

    public function getUniqueId(): string
    {
        return spl_object_hash($this);
    }
}
