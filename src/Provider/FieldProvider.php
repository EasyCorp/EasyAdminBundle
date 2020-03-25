<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Provider;

use Doctrine\DBAL\Types\Type;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;

final class FieldProvider
{
    public function getDefaultFields(string $pageName, EntityDto $entityDto): array
    {
        $defaultPropertyNames = [];
        $maxNumProperties = Crud::PAGE_INDEX === $pageName ? 7 : \PHP_INT_MAX;

        $excludedPropertyTypes = [
            Crud::PAGE_EDIT => [Type::BINARY, Type::BLOB, Type::JSON_ARRAY, Type::JSON, Type::OBJECT],
            Crud::PAGE_INDEX => [Type::TARRAY , Type::BINARY, Type::BLOB, Type::GUID, Type::JSON_ARRAY, Type::JSON, Type::OBJECT, Type::SIMPLE_ARRAY, Type::TEXT],
            Crud::PAGE_NEW => [Type::BINARY, Type::BLOB, Type::JSON_ARRAY, Type::JSON, Type::OBJECT],
            Crud::PAGE_DETAIL => [],
        ];

        $excludedPropertyNames = [
            Crud::PAGE_EDIT => [$entityDto->getPrimaryKeyName()],
            Crud::PAGE_INDEX => ['password', 'salt', 'slug', 'updatedAt', 'uuid'],
            Crud::PAGE_NEW => [$entityDto->getPrimaryKeyName()],
            Crud::PAGE_DETAIL => [],
        ];

        foreach ($entityDto->getAllPropertyNames() as $propertyName) {
            $metadata = $entityDto->getPropertyMetadata($propertyName);
            if (!\in_array($propertyName, $excludedPropertyNames[$pageName], true) && !\in_array($metadata['type'], $excludedPropertyTypes[$pageName], true)) {
                $defaultPropertyNames[] = $propertyName;
            }
        }

        if (\count($defaultPropertyNames) > $maxNumProperties) {
            $defaultPropertyNames = \array_slice($defaultPropertyNames, 0, $maxNumProperties, true);
        }

        return array_map(static function (string $fieldName) {
            return Field::new($fieldName);
        }, $defaultPropertyNames);
    }
}
