<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Property;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfigInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class AssociationProperty implements PropertyConfigInterface
{
    use PropertyConfigTrait;

    public const OPTION_CRUD_CONTROLLER = 'crudControllerFqcn';
    // these options are intended for internal use only
    public const OPTION_TYPE = 'type';
    public const OPTION_RELATED_URL = 'relatedUrl';

    public function __construct()
    {
        $this
            ->setType('association')
            ->setFormType(EntityType::class)
            ->setTemplateName('property/association')
            ->setCustomOption(self::OPTION_CRUD_CONTROLLER, null)
            ->setCustomOption(self::OPTION_RELATED_URL, null)
            ->setCustomOption(self::OPTION_TYPE, null);
    }

    public function setCrudController(string $crudControllerFqcn): self
    {
        $this->setCustomOption(self::OPTION_CRUD_CONTROLLER, $crudControllerFqcn);

        return $this;
    }
}
