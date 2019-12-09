<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Builder;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use EasyCorp\Bundle\EasyAdminBundle\Collection\EntityDtoCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\PropertyDtoCollection;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\ItemCollectionBuilderInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityBuiltEvent;
use EasyCorp\Bundle\EasyAdminBundle\Exception\EntityNotFoundException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class EntityBuilder
{
    private $doctrine;
    private $eventDispatcher;
    private $propertyBuilder;

    public function __construct(ManagerRegistry $doctrine, EventDispatcherInterface $eventDispatcher, ItemCollectionBuilderInterface $propertyBuilder)
    {
        $this->doctrine = $doctrine;
        $this->eventDispatcher = $eventDispatcher;
        $this->propertyBuilder = $propertyBuilder;
    }

    public function buildFromEntityFqcn(string $entityFqcn, PropertyDtoCollection $propertiesDto): EntityDto
    {
        $entityManager = $this->getEntityManager($entityFqcn);
        $entityMetadata = $entityManager->getClassMetadata($entityFqcn);

        if (1 !== count($entityMetadata->getIdentifierFieldNames())) {
            throw new \RuntimeException(sprintf('EasyAdmin does not support Doctrine entities with composite primary keys (such as the ones used in the "%s" entity).', $entityFqcn));
        }

        $entityPropertiesDto = $this->propertyBuilder->setItems(iterator_to_array($propertiesDto))->build();

        $entityDto = new EntityDto($entityFqcn, $entityMetadata, $entityPropertiesDto);

        $this->eventDispatcher->dispatch(new AfterEntityBuiltEvent($entityDto));

        return $entityDto;
    }

    public function buildFromEntityId(string $entityFqcn, $entityId, PropertyDtoCollection $propertiesDto): EntityDto
    {
        $entityDto = $this->buildFromEntityFqcn($entityFqcn, $propertiesDto);

        $entityManager = $this->getEntityManager($entityFqcn);
        $entityInstance = null === $entityId ? null : $this->getEntityInstance($entityManager, $entityFqcn, $entityId);

        return $this->buildFromEntityInstance($entityDto, $entityInstance);
    }

    public function buildFromEntityInstance(EntityDto $entityDto, $entityInstance): EntityDto
    {
        $newProperties = $this->propertyBuilder
            ->setItems(iterator_to_array($entityDto->getProperties()))
            ->buildForEntity($entityDto->with(['instance' => $entityInstance]));

        $newEntityDto = $entityDto->with(['propertiesDto' => $newProperties]);
        $this->eventDispatcher->dispatch(new AfterEntityBuiltEvent($newEntityDto));

        return $newEntityDto;
    }

    public function buildFromEntityInstances(EntityDto $entityDto, array $entityInstances): EntityDtoCollection
    {
        $entitiesDto = [];
        foreach ($entityInstances as $entityInstance) {
            $entitiesDto[] = $this->buildFromEntityInstance($entityDto, $entityInstance);
        }

        return EntityDtoCollection::new($entitiesDto);
    }

    private function getEntityManager(string $entityClass): ObjectManager
    {
        if (null === $entityManager = $this->doctrine->getManagerForClass($entityClass)) {
            throw new \RuntimeException(sprintf('There is no Doctrine Entity Manager defined for the "%s" class', $entityClass));
        }

        return $entityManager;
    }

    private function getEntityInstance(ObjectManager $entityManager, string $entityFqcn, $entityIdValue)
    {
        if (null === $entityInstance = $entityManager->getRepository($entityFqcn)->find($entityIdValue)) {
            $entityIdName = $entityManager->getClassMetadata($entityFqcn)->getIdentifierFieldNames()[0];

            throw new EntityNotFoundException(['entity_name' => $entityFqcn, 'entity_id_name' => $entityIdName, 'entity_id_value' => $entityIdValue]);
        }

        return $entityInstance;
    }
}
