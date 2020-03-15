<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class AssociationField implements FieldInterface
{
    use FieldTrait;

    public const OPTION_AUTOCOMPLETE = 'autocomplete';
    public const OPTION_CRUD_CONTROLLER = 'crudControllerFqcn';
    /** @internal this option is intended for internal use only */
    public const OPTION_RELATED_URL = 'relatedUrl';
    /** @internal this option is intended for internal use only */
    public const OPTION_DOCTRINE_ASSOCIATION_TYPE = 'associationType';

    public function __construct()
    {
        $this
            ->setType('association')
            ->setFormType(EntityType::class)
            ->setTemplateName('crud/field/association')
            ->setCustomOption(self::OPTION_AUTOCOMPLETE, false)
            ->setCustomOption(self::OPTION_CRUD_CONTROLLER, null)
            ->setCustomOption(self::OPTION_RELATED_URL, null)
            ->setCustomOption(self::OPTION_DOCTRINE_ASSOCIATION_TYPE, null);
    }

    public function autocomplete(): self
    {
        $this->setCustomOption(self::OPTION_AUTOCOMPLETE, true);

        return $this;
    }

    public function setCrudController(string $crudControllerFqcn): self
    {
        $this->setCustomOption(self::OPTION_CRUD_CONTROLLER, $crudControllerFqcn);

        return $this;
    }
}
