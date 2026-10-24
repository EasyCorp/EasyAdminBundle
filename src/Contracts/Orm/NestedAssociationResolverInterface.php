<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Contracts\Orm;

use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\ResolvedPropertyDto;

/**
 * Resolves property paths that traverse Doctrine associations and/or embeddables (e.g. 'author.address.country').
 *
 * It adds the needed JOIN clauses to the given query builder.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface NestedAssociationResolverInterface
{
    /**
     * @param QueryBuilder|null $queryBuilder           when null, the property path is resolved using only the
     *                                                  entity metadata and no JOIN clauses are created
     * @param bool              $mustEndWithAssociation when true, the property path can end in an association
     *                                                  (e.g. 'author.address') instead of a regular property
     *
     * @throws \InvalidArgumentException when the property path doesn't match the entity metadata
     */
    public function resolveNestedAssociations(?QueryBuilder $queryBuilder, EntityDto $rootEntityDto, string $propertyName, bool $mustEndWithAssociation = false): ResolvedPropertyDto;
}
