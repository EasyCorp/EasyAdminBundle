<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Property;

use EasyCorp\Bundle\EasyAdminBundle\Context\ApplicationContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\PropertyDto;

final class Property implements PropertyInterface
{
    use PropertyTrait;

    public function __construct()
    {
        $this->type = 'generic';
        $this->templateName = 'property/generic';
    }

    public static function new(string $formTypeFqcn, string $name, ?string $label = null): self
    {
        if (!class_exists($formTypeFqcn)) {
            throw new \InvalidArgumentException(sprintf('The "%s" class used as the form type of the "%s" property is not defined in the application.', $formTypeFqcn, $name));
        }

        $property = new static();
        $property->formType = $formTypeFqcn;
        $property->type = basename(str_replace('\\', '/', $formTypeFqcn));
        $property->name = $name;
        $property->label = $label;

        return $property;
    }

    public function build(PropertyDto $propertyDto, EntityDto $entityDto, ApplicationContext $applicationContext): PropertyDto
    {
        return $propertyDto;
    }
}
