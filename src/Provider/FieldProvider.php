<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Provider;

use Doctrine\DBAL\Types\Types;
use EasyCorp\Bundle\EasyAdminBundle\Config\CrudInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;

final class FieldProvider implements FieldProviderInterface
{
    private AdminContextProviderInterface $adminContextProvider;

    public function __construct(AdminContextProviderInterface $adminContextProvider)
    {
        $this->adminContextProvider = $adminContextProvider;
    }

    public function getDefaultFields(string $pageName): array
    {
        $defaultPropertyNames = [];
        $maxNumProperties = CrudInterface::PAGE_INDEX === $pageName ? 7 : \PHP_INT_MAX;
        $entityDto = $this->adminContextProvider->getContext()->getEntity();

        $excludedPropertyTypes = [
            CrudInterface::PAGE_EDIT => [Types::BINARY, Types::BLOB, Types::JSON, Types::OBJECT],
            CrudInterface::PAGE_INDEX => [
                Types::BINARY,
                Types::BLOB,
                Types::GUID,
                Types::JSON,
                Types::OBJECT,
                Types::TEXT,
            ],
            CrudInterface::PAGE_NEW => [Types::BINARY, Types::BLOB, Types::JSON, Types::OBJECT],
            CrudInterface::PAGE_DETAIL => [Types::BINARY, Types::JSON, Types::OBJECT],
        ];

        $excludedPropertyNames = [
            CrudInterface::PAGE_EDIT => [$entityDto->getPrimaryKeyName()],
            CrudInterface::PAGE_INDEX => ['password', 'salt', 'slug', 'updatedAt', 'uuid'],
            CrudInterface::PAGE_NEW => [$entityDto->getPrimaryKeyName()],
            CrudInterface::PAGE_DETAIL => [],
        ];

        foreach ($entityDto->getAllPropertyNames() as $propertyName) {
            $metadata = $entityDto->getPropertyMetadata($propertyName);
            if (!\in_array($propertyName, $excludedPropertyNames[$pageName], true) && !\in_array(
                    $metadata->get('type'),
                    $excludedPropertyTypes[$pageName],
                    true
                )) {
                $defaultPropertyNames[] = $propertyName;
            }
        }

        if (\count($defaultPropertyNames) > $maxNumProperties) {
            $defaultPropertyNames = \array_slice($defaultPropertyNames, 0, $maxNumProperties, true);
        }

        return array_map(static fn(string $fieldName) => Field::new($fieldName), $defaultPropertyNames);
    }
}
