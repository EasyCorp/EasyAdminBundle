<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Contracts\Factory;

use Doctrine\ORM\Mapping\ClassMetadata;
use EasyCorp\Bundle\EasyAdminBundle\Collection\ActionCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\EntityCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Dto\ActionConfigDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use Symfony\Component\ExpressionLanguage\Expression;

interface EntityFactoryInterface
{
    public function processFields(EntityDto $entityDto, FieldCollection $fields): void;

    public function processFieldsForAll(EntityCollection $entities, FieldCollection $fields): void;

    public function processActions(EntityDto $entityDto, ActionConfigDto $actionConfigDto): void;

    public function processActionsForAll(EntityCollection $entities, ActionConfigDto $actionConfigDto): ActionCollection;

    public function create(string $entityFqcn, $entityId = null, string|Expression|null $entityPermission = null): EntityDto;

    public function createForEntityInstance($entityInstance): EntityDto;

    public function createCollection(EntityDto $entityDto, ?iterable $entityInstances): EntityCollection;

    public function getEntityMetadata(string $entityFqcn): ClassMetadata;
}
