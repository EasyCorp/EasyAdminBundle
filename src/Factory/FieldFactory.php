<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Factory;

use Doctrine\DBAL\Types\Type;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextAreaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TimeField;
use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Security\Permission;
use EasyCorp\Bundle\EasyAdminBundle\Transformer\FieldTransformer;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class FieldFactory
{
    // TODO: update this map when ArrayField is implemented
    private const DOCTRINE_TYPE_TO_FIELD_FQCN_MAP = [
        //Type::TARRAY => 'array',
        Type::BIGINT => TextField::class,
        Type::BINARY => TextAreaField::class,
        Type::BLOB => TextAreaField::class,
        Type::BOOLEAN => BooleanField::class,
        Type::DATE => DateField::class,
        Type::DATE_IMMUTABLE => DateField::class,
        Type::DATEINTERVAL => TextField::class,
        Type::DATETIME => DateTimeField::class,
        Type::DATETIME_IMMUTABLE => DateTimeField::class,
        Type::DATETIMETZ => 'datetimetz',
        Type::DATETIMETZ_IMMUTABLE => 'datetimetz',
        Type::DECIMAL => NumberField::class,
        Type::FLOAT => NumberField::class,
        Type::GUID => TextField::class,
        Type::INTEGER => IntegerField::class,
        Type::JSON => TextField::class,
        Type::OBJECT => TextField::class,
        //Type::SIMPLE_ARRAY => 'array',
        Type::SMALLINT => IntegerField::class,
        Type::STRING => TextField::class,
        Type::TEXT => TextAreaField::class,
        Type::TIME => TimeField::class,
        Type::TIME_IMMUTABLE => TimeField::class,
    ];

    private $adminContextProvider;
    private $authorizationChecker;
    private $fieldConfigurators;

    public function __construct(AdminContextProvider $adminContextProvider, AuthorizationCheckerInterface $authorizationChecker, iterable $fieldConfigurators)
    {
        $this->adminContextProvider = $adminContextProvider;
        $this->authorizationChecker = $authorizationChecker;
        $this->fieldConfigurators = $fieldConfigurators;
    }

    public function processFields(EntityDto &$entityDto, FieldCollection $fields): void
    {
        $this->preProcessFields($fields, $entityDto);

        foreach ($fields as $fieldName => $fieldDto) {
            if (false === $this->authorizationChecker->isGranted(Permission::EA_VIEW_FIELD, $fieldDto)) {
                continue;
            }

            foreach ($this->fieldConfigurators as $configurator) {
                if (!$configurator->supports($fieldDto, $entityDto)) {
                    continue;
                }

                $configurator->configure($fieldDto, $entityDto, $this->adminContextProvider->getContext());
            }

            $fields->set($fieldName, $fieldDto);
        }

        $entityDto->setFields($fields);
    }

    /**
     * @param FieldInterface[] $fields
     */
/*
    public function create(EntityDto $entityDto, iterable $fields): EntityDto
    {
        $action = $this->adminContextProvider->getContext()->getCrud()->getCurrentAction();
        $configuredProperties = \is_array($fields) ? $fields : iterator_to_array($fields);
        $configuredProperties = $this->preProcessFields($entityDto, $configuredProperties);

        $builtProperties = [];
        foreach ($configuredProperties as $field) {
            if (false === $this->authorizationChecker->isGranted(Permission::EA_VIEW_FIELD, $field)) {
                continue;
            }

            foreach ($this->fieldConfigurators as $configurator) {
                if (!$configurator->supports($field, $entityDto)) {
                    continue;
                }

                $configurator->configure($field, $entityDto, $action);
            }

            $builtProperties[] = $field->getAsDto();
        }

        return $entityDto->updateFields(FieldDtoCollection::new($builtProperties));
    }
*/

    private function preProcessFields(FieldCollection $fields, EntityDto $entityDto): void
    {
        foreach ($fields as $fieldName => $fieldDto) {
            if (Field::class !== $fieldDto->getFieldFqcn()) {
                continue;
            }

            // this is a virtual field, so we can't autoconfigure it
            if (!$entityDto->hasProperty($fieldDto->getName())) {
                continue;
            }

            if ($fieldName === $entityDto->getPrimaryKeyName()) {
                $guessedFieldFqcn = IdField::class;
            } else {
                $doctrinePropertyType = $entityDto->getPropertyMetadata($fieldName)['type'];
                $guessedFieldFqcn = self::DOCTRINE_TYPE_TO_FIELD_FQCN_MAP[$doctrinePropertyType] ?? null;
            }

            $fields->set($fieldName, $this->transformField($fieldDto, $guessedFieldFqcn));
        }
    }

    private function transformField(FieldDto $fieldDto, string $newFieldFqcn): FieldDto
    {
        /** @var FieldDto $newField */
        $newField = $newFieldFqcn::new($fieldDto->getName())->getAsDto();

        $newField->setValue($fieldDto->getValue());
        $newField->setFormattedValue($fieldDto->getFormattedValue());
        $newField->setTranslationParameters($fieldDto->getTranslationParameters());
        $newField->setAssets($newField->getAssets()->mergeWith($fieldDto->getAssets()));

        $customFormTypeOptions = $fieldDto->getFormTypeOptions();
        $defaultFormTypeOptions = $newField->getFormTypeOptions();
        $newField->setFormTypeOptions(array_merge($defaultFormTypeOptions, $customFormTypeOptions));

        $customFieldOptions = $fieldDto->getCustomOptions()->all();
        $defaultFieldOptions = $newField->getCustomOptions()->all();
        $mergedFieldOptions = array_merge($defaultFieldOptions, $customFieldOptions);
        $newField->setCustomOptions(new ParameterBag($mergedFieldOptions));

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

        if (null !== $fieldDto->getCssClass()) {
            $newField->setCssClass($fieldDto->getCssClass());
        }

        if (null !== $fieldDto->getFormType()) {
            $newField->setFormType($fieldDto->getFormType());
        }

        if (null !== $fieldDto->getTemplateName()) {
            $newField->setTemplateName($fieldDto->getTemplateName());
        }

        if (null !== $fieldDto->getTemplatePath()) {
            $newField->setTemplatePath($fieldDto->getTemplatePath());
        }

        return $newField;
    }
}
