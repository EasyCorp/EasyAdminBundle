<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Property;

use EasyCorp\Bundle\EasyAdminBundle\Context\ApplicationContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\PropertyDto;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class IdProperty implements PropertyInterface
{
    use PropertyTrait;

    public function __construct()
    {
        $this->type = 'id';
        $this->formType = TextType::class;
        $this->templateName = 'property/id';
    }

    public function build(PropertyDto $propertyDto, EntityDto $entityDto, ApplicationContext $applicationContext): PropertyDto
    {
        return $propertyDto;
    }
}
