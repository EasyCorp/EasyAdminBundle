<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Factory;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\FieldMapping;
use EasyCorp\Bundle\EasyAdminBundle\Collection\EntityCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Provider\AdminContextProviderInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TimeField;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\Layout\EaFormRowType;
use EasyCorp\Bundle\EasyAdminBundle\Security\Permission;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class FieldFactory
{
    /**
     * @var array<string, class-string<FieldInterface>>
     */
    private static array $doctrineTypeToFieldFqcn = [
        'array' => ArrayField::class, // don't use Types::ARRAY because it was removed in Doctrine DBAL 4
        Types::BIGINT => TextField::class,
        Types::BINARY => TextareaField::class,
        Types::BLOB => TextareaField::class,
        Types::BOOLEAN => BooleanField::class,
        Types::DATE_MUTABLE => DateField::class,
        Types::DATE_IMMUTABLE => DateField::class,
        Types::DATEINTERVAL => TextField::class,
        Types::DATETIME_MUTABLE => DateTimeField::class,
        Types::DATETIME_IMMUTABLE => DateTimeField::class,
        Types::DATETIMETZ_MUTABLE => DateTimeField::class,
        Types::DATETIMETZ_IMMUTABLE => DateTimeField::class,
        Types::DECIMAL => NumberField::class,
        Types::FLOAT => NumberField::class,
        Types::GUID => TextField::class,
        Types::INTEGER => IntegerField::class,
        Types::JSON => ArrayField::class,
        'object' => TextField::class, // don't use Types::OBJECT because it was removed in Doctrine DBAL 4
        Types::SIMPLE_ARRAY => ArrayField::class,
        Types::SMALLINT => IntegerField::class,
        Types::STRING => TextField::class,
        Types::TEXT => TextareaField::class,
        Types::TIME_MUTABLE => TimeField::class,
        Types::TIME_IMMUTABLE => TimeField::class,
    ];

    /**
     * @param iterable<FieldConfiguratorInterface> $fieldConfigurators
     */
    public function __construct(
        private readonly AdminContextProviderInterface $adminContextProvider,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly iterable $fieldConfigurators,
        private readonly FormLayoutFactory $fieldLayoutFactory)
    {
    }

    public function processFields(EntityDto $entityDto, FieldCollection $fields, ?string $currentPage = null): void
    {
        $this->replaceGenericFieldsWithSpecificFields($fields, $entityDto);

        $context = $this->adminContextProvider->getContext();

        if (null === $currentPage) {
            trigger_deprecation(
                'easycorp/easyadmin-bundle',
                '4.27.0',
                'Argument "$currentPage" is missing. Omitting it will cause an error in 5.0.0.',
            );
            $currentPage = $context->getCrud()->getCurrentPage();
        }

        $isDetailOrIndex = \in_array($currentPage, [Crud::PAGE_INDEX, Crud::PAGE_DETAIL], true);
        foreach ($fields as $fieldDto) {
            if ((null !== $currentPage && false === $fieldDto->isDisplayedOn($currentPage))
                || false === $this->authorizationChecker->isGranted(Permission::EA_VIEW_FIELD, $fieldDto)) {
                $fields->unset($fieldDto);

                continue;
            }

            // "form rows" only make sense in pages that contain forms
            if ($isDetailOrIndex && EaFormRowType::class === $fieldDto->getFormType()) {
                $fields->unset($fieldDto);

                continue;
            }

            foreach ($this->fieldConfigurators as $configurator) {
                if (!$configurator->supports($fieldDto, $entityDto)) {
                    continue;
                }

                // @phpstan-ignore-next-line argument.type
                $configurator->configure($fieldDto, $entityDto, $context);
            }

            // check again if the field is displayed because this can change in the configurators
            if (null !== $currentPage && false === $fieldDto->isDisplayedOn($currentPage)) {
                $fields->unset($fieldDto);
                continue;
            }

            foreach ($fieldDto->getFormThemes() as $formThemePath) {
                $context->getCrud()->addFormTheme($formThemePath);
            }

            $fields->set($fieldDto);
        }

        if (!$fields->isEmpty()) {
            $this->fieldLayoutFactory->createLayout($fields, $this->adminContextProvider->getContext()?->getCrud()?->getCurrentPage() ?? Crud::PAGE_INDEX);
        }

        $entityDto->setFields($fields);
    }

    public function processFieldsForAll(EntityCollection $entityDtos, FieldCollection $fields, ?string $currentPage = null): void
    {
        if (null === $currentPage) {
            trigger_deprecation(
                'easycorp/easyadmin-bundle',
                '4.27.0',
                'Argument "$currentPage" is missing. Omitting it will cause an error in 5.0.0.',
            );
        }

        foreach ($entityDtos as $entityDto) {
            $this->processFields($entityDto, clone $fields, $currentPage);
            $entityDtos->set($entityDto);
        }
    }

    private function replaceGenericFieldsWithSpecificFields(FieldCollection $fields, EntityDto $entityDto): void
    {
        foreach ($fields as $fieldDto) {
            if (Field::class !== $fieldDto->getFieldFqcn()) {
                continue;
            }

            // this is a virtual field, so we can't autoconfigure it
            if (!isset($entityDto->getClassMetadata()->fieldMappings[$fieldDto->getProperty()])
                && !$entityDto->getClassMetadata()->hasAssociation($fieldDto->getProperty())) {
                continue;
            }

            if ($fieldDto->getProperty() === $entityDto->getClassMetadata()->getSingleIdentifierFieldName()) {
                $guessedFieldFqcn = IdField::class;
            } elseif ($entityDto->getClassMetadata()->hasAssociation($fieldDto->getProperty())) {
                /** @var bool $orphanRemoval */
                $orphanRemoval = $entityDto->getClassMetadata()->getAssociationMapping($fieldDto->getProperty())['orphanRemoval'];
                if ($orphanRemoval && $entityDto->getClassMetadata()->isCollectionValuedAssociation($fieldDto->getProperty())) {
                    $guessedFieldFqcn = CollectionField::class;
                } else {
                    $guessedFieldFqcn = AssociationField::class;
                }
            } elseif (!isset($entityDto->getClassMetadata()->fieldMappings[$fieldDto->getProperty()])) {
                throw new \RuntimeException(sprintf('Could not guess a field class for "%s" field. It possibly is an association field or an embedded class field.', $fieldDto->getProperty()));
            } else {
                // In Doctrine ORM 3.x, FieldMapping implements \ArrayAccess; in 4.x it's an object with properties
                $fieldMapping = $entityDto->getClassMetadata()->getFieldMapping($fieldDto->getProperty());
                // In Doctrine ORM 2.x, getFieldMapping() returns an array
                /** @phpstan-ignore-next-line function.impossibleType */
                if (\is_array($fieldMapping)) {
                    /** @phpstan-ignore-next-line cast.useless */
                    $fieldMapping = (object) $fieldMapping;
                }
                /** @phpstan-ignore-next-line function.alreadyNarrowedType */
                $fieldType = property_exists($fieldMapping, 'type') ? $fieldMapping->type : $fieldMapping['type'];
                $guessedFieldFqcn = self::$doctrineTypeToFieldFqcn[$fieldType] ?? null;
                if (null === $guessedFieldFqcn) {
                    throw new \RuntimeException(sprintf('The Doctrine type of the "%s" field is "%s", which is not supported by EasyAdmin. For Doctrine\'s Custom Mapping Types have a look at EasyAdmin\'s field docs.', $fieldDto->getProperty(), $fieldType));
                }
            }

            $fields->set($this->createSpecificFieldFromGenericField($fieldDto, $guessedFieldFqcn));
        }
    }

    /**
     * Creates a DTO of a specific field (e.g. DateTimeField) from a DTO of the generic Field.
     */
    private function createSpecificFieldFromGenericField(FieldDto $fieldDto, string $newFieldFqcn): FieldDto
    {
        /** @var FieldDto $newField */
        $newField = $newFieldFqcn::new($fieldDto->getProperty())->getAsDto();
        $newField->setUniqueId($fieldDto->getUniqueId());

        $newField->setFieldFqcn($newFieldFqcn);
        $newField->setDisplayedOn($fieldDto->getDisplayedOn());
        $newField->setValue($fieldDto->getValue());
        $newField->setFormattedValue($fieldDto->getFormattedValue());
        $newField->setCssClass(trim($newField->getCssClass().' '.$fieldDto->getCssClass()));
        $newField->setColumns($fieldDto->getColumns());
        $newField->setTranslationParameters($fieldDto->getTranslationParameters());
        $newField->setAssets($newField->getAssets()->mergeWith($fieldDto->getAssets()));
        foreach ($fieldDto->getFormThemes() as $formThemePath) {
            $newField->addFormTheme($formThemePath);
        }

        $customFormTypeOptions = $fieldDto->getFormTypeOptions();
        $defaultFormTypeOptions = $newField->getFormTypeOptions();
        $newField->setFormTypeOptions(array_merge($defaultFormTypeOptions, $customFormTypeOptions));

        $customFieldOptions = $fieldDto->getCustomOptions()->all();
        $defaultFieldOptions = $newField->getCustomOptions()->all();
        $mergedFieldOptions = array_merge($defaultFieldOptions, $customFieldOptions);
        $newField->setCustomOptions($mergedFieldOptions);

        if (null !== $fieldDto->getLabel()) {
            $newField->setLabel($fieldDto->getLabel());
        }

        if (null !== $fieldDto->isVirtual()) {
            $newField->setVirtual($fieldDto->isVirtual());
        }

        if (null !== $fieldDto->getTextAlign()) {
            $newField->setTextAlign($fieldDto->getTextAlign());
        }

        if (null !== $fieldDto->isSortable()) {
            $newField->setSortable($fieldDto->isSortable());
        }

        if (null !== $fieldDto->getPermission()) {
            $newField->setPermission($fieldDto->getPermission());
        }

        if (null !== $fieldDto->getHelp()) {
            $newField->setHelp($fieldDto->getHelp());
        }

        if (null !== $fieldDto->getFormType()) {
            $newField->setFormType($fieldDto->getFormType());
        }

        // copy the template path of the original Field class if it was customized
        if (null !== $fieldDto->getTemplatePath()) {
            $newField->setTemplatePath($fieldDto->getTemplatePath());
        }

        return $newField;
    }
}
