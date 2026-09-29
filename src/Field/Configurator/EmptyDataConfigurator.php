<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field\Configurator;

use Doctrine\DBAL\Types\Types;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\CodeEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ColorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;

/**
 * Symfony's TextType only turns an empty submitted value into an empty string when the
 * 'empty_data' option is explicitly set to ''. Otherwise it passes NULL to the entity setter,
 * which fails with a TypeError when the property is typed as string (and that error hides the
 * NotBlank violation that should have been displayed as a regular form error).
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class EmptyDataConfigurator implements FieldConfiguratorInterface
{
    /** All these fields use form types based on Symfony's TextType */
    private const TEXT_BASED_FIELDS = [
        CodeEditorField::class,
        ColorField::class,
        EmailField::class,
        SlugField::class,
        TelephoneField::class,
        TextField::class,
        TextEditorField::class,
        TextareaField::class,
        UrlField::class,
    ];

    private const TEXT_BASED_DOCTRINE_TYPES = [
        Types::ASCII_STRING,
        Types::GUID,
        Types::STRING,
        Types::TEXT,
    ];

    public function supports(FieldDto $field, EntityDto $entityDto): bool
    {
        return \in_array($field->getFieldFqcn(), self::TEXT_BASED_FIELDS, true);
    }

    public function configure(FieldDto $field, EntityDto $entityDto, AdminContext $context): void
    {
        $doctrineMetadata = $field->getDoctrineMetadata();

        // this also discards virtual fields, which don't have any Doctrine metadata
        if (!\in_array($doctrineMetadata->get('type'), self::TEXT_BASED_DOCTRINE_TYPES, true)) {
            return;
        }

        // Doctrine doesn't store the 'nullable' value in its metadata cache when it's FALSE, so in
        // production this value is NULL for non-nullable properties. That's why anything other than
        // TRUE must be treated as non-nullable (CommonPreConfigurator does the same to set 'required')
        if (true === $doctrineMetadata->get('nullable')) {
            return;
        }

        // a string-backed enum may not define an empty string as a valid case,
        // so don't apply the default empty string to enum properties
        if (null !== $doctrineMetadata->get('enumType')) {
            return;
        }

        $field->setFormTypeOptionIfNotSet('empty_data', '');
    }
}
