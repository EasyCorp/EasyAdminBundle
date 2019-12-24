<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Configuration\Property;

use EasyCorp\Bundle\EasyAdminBundle\Dto\AssetDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\PropertyDto;

trait PropertyConfigTrait
{
    use PropertyConfigPropertiesTrait;
    use PropertyConfigSettersTrait {
        setName as private;
        setLabel as private;
        setValue as private;
        setFormattedValue as private;
        setVirtual as private;
        setResolvedTemplatePath as private;
    }

    private function __construct()
    {
    }

    public static function new(string $name, ?string $label = null): self
    {
        $property = new static();
        $property->name = $name;
        $property->label = $label;

        return $property;
    }

    public function getAsDto(): PropertyDto
    {
        return new PropertyDto($this->name, $this->type, $this->formType, $this->formTypeOptions ?? [], $this->sortable, $this->label, $this->permission, $this->textAlign ?? 'left', $this->help, $this->cssClass, $this->translationParams ?? [], $this->templateName, $this->templatePath, new AssetDto($this->cssFiles ?? [], $this->jsFiles ?? [], $this->headContents ?? [], $this->bodyContents ?? []), $this->customOptions ?? []);
    }
}
