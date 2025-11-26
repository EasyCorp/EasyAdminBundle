<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Provider;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\FieldMapping;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Provider\AdminContextProviderInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class FieldProvider
{
    public function __construct(
        private readonly AdminContextProviderInterface $adminContextProvider,
    ) {
    }

    /**
     * @return array<Field>
     */
    public function getDefaultFields(string $pageName): array
    {
        $defaultPropertyNames = [];
        $maxNumProperties = Crud::PAGE_INDEX === $pageName ? 7 : \PHP_INT_MAX;
        $entityDto = $this->adminContextProvider->getContext()->getEntity();

        // don't use Types::OBJECT because it was removed in Doctrine ORM 3.0
        $excludedPropertyTypes = [
            Crud::PAGE_EDIT => [Types::BINARY, Types::BLOB, Types::JSON, 'object'],
            Crud::PAGE_INDEX => [Types::BINARY, Types::BLOB, Types::GUID, Types::JSON, 'object', Types::TEXT],
            Crud::PAGE_NEW => [Types::BINARY, Types::BLOB, Types::JSON, 'object'],
            Crud::PAGE_DETAIL => [Types::BINARY, Types::JSON, 'object'],
        ];

        $excludedPropertyNames = [
            Crud::PAGE_EDIT => [$entityDto->getClassMetadata()->getSingleIdentifierFieldName()],
            Crud::PAGE_INDEX => ['password', 'salt', 'slug', 'updatedAt', 'uuid'],
            Crud::PAGE_NEW => [$entityDto->getClassMetadata()->getSingleIdentifierFieldName()],
            Crud::PAGE_DETAIL => [],
        ];

        foreach ($entityDto->getClassMetadata()->getFieldNames() as $propertyName) {
            $isFieldMapping = isset($entityDto->getClassMetadata()->fieldMappings[$propertyName]);

            // In Doctrine ORM 3.x, FieldMapping implements \ArrayAccess; in 4.x it's an object with properties
            $fieldMapping = $isFieldMapping ? $entityDto->getClassMetadata()->getFieldMapping($propertyName) : null;
            $fieldMappingType = null;
            if ($isFieldMapping) {
                // In Doctrine ORM 2.x, getFieldMapping() returns an array
                /** @phpstan-ignore-next-line function.impossibleType */
                if (\is_array($fieldMapping)) {
                    /** @phpstan-ignore-next-line cast.useless */
                    $fieldMapping = (object) $fieldMapping;
                }

                /** @phpstan-ignore-next-line function.alreadyNarrowedType */
                $fieldMappingType = property_exists($fieldMapping, 'type') ? $fieldMapping->type : $fieldMapping['type'];
            }

            if (!\in_array($propertyName, $excludedPropertyNames[$pageName] ?? [], true)
                && (!$isFieldMapping || !\in_array($fieldMappingType, $excludedPropertyTypes[$pageName] ?? [], true))) {
                $defaultPropertyNames[] = $propertyName;
            }
        }

        if (\count($defaultPropertyNames) > $maxNumProperties) {
            $defaultPropertyNames = \array_slice($defaultPropertyNames, 0, $maxNumProperties, true);
        }

        return array_map(static fn (string $fieldName) => Field::new($fieldName), $defaultPropertyNames);
    }
}
