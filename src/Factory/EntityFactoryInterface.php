<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Factory;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Persistence\Mapping\ClassMetadata;
use EasyCorp\Bundle\EasyAdminBundle\Collection\ActionCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\EntityCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Dto\ActionConfigDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;

interface EntityFactoryInterface
{
    public function processFields(EntityDto $entityDto, FieldCollection $fields): void;

    public function processFieldsForAll(EntityCollection $entities, FieldCollection $fields): void;

    public function processActions(EntityDto $entityDto, ActionConfigDto $actionConfigDto): void;

    public function processActionsForAll(
        EntityCollection $entities,
        ActionConfigDto $actionConfigDto
    ): ActionCollection;

    public function create(string $entityFqcn, $entityId = null, ?string $entityPermission = null): EntityDto;

    public function createForEntityInstance($entityInstance): EntityDto;

    public function createCollection(EntityDto $entityDto, ?iterable $entityInstances): EntityCollection;

    /**
     * @return ClassMetadata&ClassMetadataInfo
     */
    public function getEntityMetadata(string $entityFqcn): ClassMetadata;
}
