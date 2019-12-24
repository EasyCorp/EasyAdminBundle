<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Property;

use EasyCorp\Bundle\EasyAdminBundle\Context\ApplicationContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfigInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\PropertyDto;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class BooleanProperty implements PropertyConfigInterface
{
    use PropertyConfigTrait;

    public function __construct()
    {
        $this->type = 'boolean';
        $this->formType = ChoiceType::class;
        $this->textAlign = 'center';
        $this->templateName = 'property/boolean';
    }

    public function build(PropertyDto $propertyDto, EntityDto $entityDto, ApplicationContext $applicationContext): PropertyDto
    {
        return $propertyDto;
    }
}
