<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Property;

use EasyCorp\Bundle\EasyAdminBundle\Context\ApplicationContext;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\PropertyDto;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class BooleanProperty extends AbstractProperty
{
    public function __construct()
    {
        $this->type = 'id';
        $this->formType = ChoiceType::class;
        $this->textAlign = 'center';
        $this->defaultTemplatePath = '@EasyAdmin/field_boolean.html.twig';
    }

    public function build(PropertyDto $propertyDto, EntityDto $entityDto, ApplicationContext $applicationContext): PropertyDto
    {
        return $propertyDto;
    }
}
