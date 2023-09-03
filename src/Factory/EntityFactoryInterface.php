<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Factory;


use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Persistence\Mapping\ClassMetadata;
use EasyCorp\Bundle\EasyAdminBundle\Collection\ActionCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\EntityCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Dto\ActionConfigDtoInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDtoInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface EntityFactoryInterface
{
    public function processFields(EntityDtoInterface $entityDto, FieldCollection $fields): void;

    public function processFieldsForAll(EntityCollection $entities, FieldCollection $fields): void;

    public function processActions(EntityDtoInterface $entityDto, ActionConfigDtoInterface $actionConfigDto): void;

    public function processActionsForAll(
        EntityCollection $entities,
        ActionConfigDtoInterface $actionConfigDto
    ): ActionCollection;

    public function create(string $entityFqcn, $entityId = null, ?string $entityPermission = null): EntityDtoInterface;

    public function createForEntityInstance($entityInstance): EntityDtoInterface;

    public function createCollection(EntityDtoInterface $entityDto, ?iterable $entityInstances): EntityCollection;

    /**
     * @return ClassMetadata&ClassMetadataInfo
     */
    public function getEntityMetadata(string $entityFqcn): ClassMetadata;
}
