<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Factory;

use Doctrine\DBAL\Types\Type;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldDtoCollection;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Security\Permission;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class FieldFactory
{
    private const DOCTRINE_TYPE_TO_PROPERTY_TYPE_MAP = [
        Type::TARRAY => 'array',
        Type::BIGINT => 'bigint',
        Type::BINARY => 'text',
        Type::BLOB => 'text',
        Type::BOOLEAN => 'boolean',
        Type::DATE => 'date',
        Type::DATE_IMMUTABLE => 'date',
        Type::DATEINTERVAL => 'text',
        Type::DATETIME => 'datetime',
        Type::DATETIME_IMMUTABLE => 'datetime',
        Type::DATETIMETZ => 'datetimetz',
        Type::DATETIMETZ_IMMUTABLE => 'datetimetz',
        Type::DECIMAL => 'decimal',
        Type::FLOAT => 'float',
        Type::GUID => 'string',
        Type::INTEGER => 'integer',
        Type::JSON => 'text',
        Type::OBJECT => 'text',
        Type::SIMPLE_ARRAY => 'array',
        Type::SMALLINT => 'integer',
        Type::STRING => 'text',
        Type::TEXT => 'textarea',
        Type::TIME => 'time',
        Type::TIME_IMMUTABLE => 'time',
    ];

    private const PROPERTY_TYPE_TO_PROPERTY_CLASSNAME_MAP = [
        'datetime' => 'DateTime',
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

        return $entityDto->updateProperties(FieldDtoCollection::new($builtProperties));
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

            $guessedType = self::DOCTRINE_TYPE_TO_PROPERTY_TYPE_MAP[$doctrineMetadata['type']] ?? null;
            if (null !== $guessedType) {
                $guessedClassName = self::PROPERTY_TYPE_TO_PROPERTY_CLASSNAME_MAP[$guessedType] ?? ucfirst($guessedType);
                $guessedPropertyClass = sprintf('EasyCorp\\Bundle\\EasyAdminBundle\\Property\\%sProperty', $guessedClassName);
                $fields[$i] = $field->transformInto($guessedPropertyClass);
            }
        }

        return $fields;
    }
}
