<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\SlugType;

/**
 * @author Jonathan Scheiber <contact@jmsche.fr>
 */
final class SlugField implements FieldInterface
{
    use FieldTrait;

    public const OPTION_TARGET_FIELD_NAME = 'targetFieldName';
    public const OPTION_UNLOCK_CONFIRMATION_MESSAGE = 'unlockConfirmationMessage';

    public static function new(string $propertyName, ?string $label = null): self
    {
        return (new self())
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setTemplateName('crud/field/text')
            ->setFormType(SlugType::class)
            ->setCustomOption(self::OPTION_TARGET_FIELD_NAME, null)
            ->setCustomOption(self::OPTION_UNLOCK_CONFIRMATION_MESSAGE, null)
            ->addCssClass('field-text')
            ->addJsFiles('bundles/easyadmin/form-type-slug.js')
        ;
    }

    public function setTargetFieldName(string $fieldName): self
    {
        $this->setCustomOption(self::OPTION_TARGET_FIELD_NAME, $fieldName);

        return $this;
    }

    public function setUnlockConfirmationMessage(string $message): self
    {
        $this->setCustomOption(self::OPTION_UNLOCK_CONFIRMATION_MESSAGE, $message);

        return $this;
    }
}
