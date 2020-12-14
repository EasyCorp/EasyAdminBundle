<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Provider;

use Doctrine\DBAL\Types\Types;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class FieldProvider
{
    private $adminContextProvider;

    public function __construct(AdminContextProvider $adminContextProvider)
    {
        $this->adminContextProvider = $adminContextProvider;
    }

    public function getDefaultFields(string $pageName): array
    {
        $defaultPropertyNames = [];
        $maxNumProperties = Crud::PAGE_INDEX === $pageName ? 7 : PHP_INT_MAX;
        $entityDto = $this->adminContextProvider->getContext()->getEntity();

        $excludedPropertyTypes = [
            Crud::PAGE_EDIT => [Types::BINARY, Types::BLOB, Types::JSON, Types::OBJECT],
            Crud::PAGE_INDEX => [Types::BINARY, Types::BLOB, Types::GUID, Types::JSON, Types::OBJECT, Types::TEXT],
            Crud::PAGE_NEW => [Types::BINARY, Types::BLOB, Types::JSON, Types::OBJECT],
            Crud::PAGE_DETAIL => [Types::BINARY, Types::JSON, Types::OBJECT],
        ];

        $excludedPropertyNames = [
            Crud::PAGE_EDIT => [$entityDto->getPrimaryKeyName()],
            Crud::PAGE_INDEX => ['password', 'salt', 'slug', 'updatedAt', 'uuid'],
            Crud::PAGE_NEW => [$entityDto->getPrimaryKeyName()],
            Crud::PAGE_DETAIL => [],
        ];

        foreach ($entityDto->getAllPropertyNames() as $propertyName) {
            $metadata = $entityDto->getPropertyMetadata($propertyName);
            if (!\in_array($propertyName, $excludedPropertyNames[$pageName], true) && !\in_array($metadata->get('type'), $excludedPropertyTypes[$pageName], true)) {
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
