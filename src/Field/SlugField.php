<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Config\Asset;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\SlugType;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * @author Jonathan Scheiber <contact@jmsche.fr>
 */
final class SlugField implements FieldInterface
{
    use FieldTrait;

    public const OPTION_TARGET_FIELD_NAME = 'targetFieldName';
    public const OPTION_UNLOCK_CONFIRMATION_MESSAGE = 'unlockConfirmationMessage';

    /**
     * @param TranslatableInterface|string|false|null $label
     */
    public static function new(string $propertyName, $label = null): self
    {
        return (new self())
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setTemplateName('crud/field/text')
            ->setFormType(SlugType::class)
            ->addCssClass('field-text')
            ->addJsFiles(Asset::fromEasyAdminAssetPackage('field-slug.js')->onlyOnForms())
            ->setDefaultColumns('col-md-6 col-xxl-5')
            ->setCustomOption(self::OPTION_TARGET_FIELD_NAME, null)
            ->setCustomOption(self::OPTION_UNLOCK_CONFIRMATION_MESSAGE, null)
        ;
    }

    /**
     * @param string|array<string> $fieldName
     */
    public function setTargetFieldName(string|array $fieldName): self
    {
        $this->setCustomOption(self::OPTION_TARGET_FIELD_NAME, \is_string($fieldName) ? [$fieldName] : $fieldName);

        return $this;
    }

    public function setUnlockConfirmationMessage(string|TranslatableInterface $message): self
    {
        $this->setCustomOption(self::OPTION_UNLOCK_CONFIRMATION_MESSAGE, $message);

        return $this;
    }
}
