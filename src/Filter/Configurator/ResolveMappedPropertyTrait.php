<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Filter\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Orm\NestedAssociationResolverInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\ResolvedPropertyDto;

/**
 * @internal
 */
trait ResolveMappedPropertyTrait
{
    private static function triggerMissingResolverDeprecation(): void
    {
        @trigger_deprecation('easycorp/easyadmin-bundle', '5.7.0', 'Not passing an instance of "%s" as the first argument of "%s::__construct()" is deprecated and it will be required in EasyAdmin 6.0.', NestedAssociationResolverInterface::class, self::class);
    }

    /**
     * Returns null when the property path doesn't end in a field mapped by Doctrine.
     */
    private function resolveMappedProperty(EntityDto $entityDto, string $propertyPath): ?ResolvedPropertyDto
    {
        // without a resolver, keep the previous behavior, which only supported properties of the root entity
        if (null === $this->associationResolver) {
            return isset($entityDto->getClassMetadata()->fieldMappings[$propertyPath])
                ? new ResolvedPropertyDto($entityDto, null, $propertyPath)
                : null;
        }

        try {
            return $this->associationResolver->resolveNestedAssociations(null, $entityDto, $propertyPath);
        } catch (\InvalidArgumentException) {
            // custom filters can use property names that are not mapped by Doctrine
            return null;
        }
    }
}
