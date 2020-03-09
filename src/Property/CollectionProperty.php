<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Property;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfigInterface;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

class CollectionProperty implements PropertyConfigInterface
{
    use PropertyConfigTrait;

    public const OPTION_ALLOW_ADD = 'allowAdd';
    public const OPTION_ALLOW_DELETE = 'allowDelete';
    public const OPTION_ENTRY_TYPE = 'entryType';
    public const OPTION_SHOW_ENTRY_LABEL = 'showEntryLabel';

    public function __construct()
    {
        $this
            ->setType('collection')
            ->setFormType(CollectionType::class)
            ->setTemplateName('property/collection')
            ->addJsFiles('bundles/easyadmin/form-type-collection.js')
            ->setCustomOption(self::OPTION_ALLOW_ADD, true)
            ->setCustomOption(self::OPTION_ALLOW_DELETE, true)
            ->setCustomOption(self::OPTION_ENTRY_TYPE, null)
            ->setCustomOption(self::OPTION_SHOW_ENTRY_LABEL, false);
    }

    public function allowAdd(bool $allow = true): self
    {
        $this->setCustomOption(self::OPTION_ALLOW_ADD, $allow);

        return $this;
    }

    public function allowDelete(bool $allow = true): self
    {
        $this->setCustomOption(self::OPTION_ALLOW_DELETE, $allow);

        return $this;
    }

    public function setEntryType(string $formTypeFqcn): self
    {
        $this->setCustomOption(self::OPTION_ENTRY_TYPE, $formTypeFqcn);

        return $this;
    }

    public function showEntryLabel(bool $showLabel = true): self
    {
        $this->setCustomOption(self::OPTION_SHOW_ENTRY_LABEL, $showLabel);

        return $this;
    }
}
