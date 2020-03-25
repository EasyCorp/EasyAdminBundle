<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Factory;

use Doctrine\DBAL\Types\Type;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldDtoCollection;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
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

    public function __construct(AdminContextProvider $adminContextProvider, AuthorizationCheckerInterface $authorizationChecker, iterable $fieldurators)
    {
        $this->adminContextProvider = $adminContextProvider;
        $this->authorizationChecker = $authorizationChecker;
        $this->fieldConfigurators = $fieldurators;
    }

    /**
     * @param FieldInterface[] $fields
     */
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

    private function preProcessFields(EntityDto $entityDto, array $fields): array
    {
        // fox DX reasons, field config can be just a string with the field name
        foreach ($fields as $i => $field) {
            if (\is_string($field)) {
                $fields[$i] = Field::new($field);
            }
        }

        /*
         * @var FieldInterface $field
         */
        foreach ($fields as $i => $field) {
            // if it's not a generic Property, don't autoconfigure it
            if (!$field instanceof Field) {
                continue;
            }

            // this is a virtual field, so we can't autoconfigure it
            if (!$entityDto->hasProperty($field->getProperty())) {
                continue;
            }

            $doctrineMetadata = $entityDto->getPropertyMetadata($field->getProperty());
            if (isset($doctrineMetadata['id']) && true === $doctrineMetadata['id']) {
                $fields[$i] = $field->transformInto(IdField::class);

                continue;
            }

            $guessedFieldFqcn = self::DOCTRINE_TYPE_TO_FIELD_FQCN_MAP[$doctrineMetadata['type']] ?? null;
            if (null !== $guessedFieldFqcn) {
                $fields[$i] = $field->transformInto($guessedFieldFqcn);
            }
        }

        return $fields;
    }
}
