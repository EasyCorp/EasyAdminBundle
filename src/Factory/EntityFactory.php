<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Factory;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\ObjectManager;
use EasyCorp\Bundle\EasyAdminBundle\Collection\EntityDtoCollection;
use EasyCorp\Bundle\EasyAdminBundle\Context\ApplicationContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityBuiltEvent;
use EasyCorp\Bundle\EasyAdminBundle\Exception\EntityNotFoundException;
use EasyCorp\Bundle\EasyAdminBundle\Security\Permission;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class EntityFactory
{
    private $applicationContextProvider;
    private $propertyFactory;
    private $authorizationChecker;
    private $doctrine;
    private $eventDispatcher;

    public function __construct(ApplicationContextProvider $applicationContextProvider, PropertyFactory $propertyFactory, AuthorizationCheckerInterface $authorizationChecker, ManagerRegistry $doctrine, EventDispatcherInterface $eventDispatcher)
    {
        $this->applicationContextProvider = $applicationContextProvider;
        $this->propertyFactory = $propertyFactory;
        $this->authorizationChecker = $authorizationChecker;
        $this->doctrine = $doctrine;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param PropertyInterface[] $configuredProperties
     */
    public function create(iterable $configuredProperties = null): EntityDto
    {
        $applicationContext = $this->applicationContextProvider->getContext();
        $entityFqcn = $applicationContext->getCrud()->getEntityFqcn();
        $entityId = $applicationContext->getRequest()->query->get('entityId');
        $entityPermission = $applicationContext->getCrud()->getPage()->getEntityPermission();

        $entityMetadata = $this->getEntityMetadata($entityFqcn);
        $entityInstance = null === $entityId ? null : $this->getEntityInstance($entityFqcn, $entityId);
        $entityDto = new EntityDto($entityFqcn, $entityMetadata, $entityPermission, $entityInstance, $entityId);

        if (!$this->authorizationChecker->isGranted(Permission::EA_VIEW_ENTITY, $entityDto)) {
            $entityDto->markAsInaccessible();
        } elseif (null !== $configuredProperties) {
            $entityDto = $this->propertyFactory->create($entityDto, $configuredProperties);
        }

        $this->eventDispatcher->dispatch(new AfterEntityBuiltEvent($entityDto));

        return $entityDto;
    }

    /**
     * @param PropertyInterface[] $propertiesConfig
     */
    public function createAll(EntityDto $entityDto, iterable $entityInstances, iterable $configuredProperties): EntityDtoCollection
    {
        return $this->propertyFactory->createAll($entityDto, $entityInstances, $configuredProperties);
    }

    private function getEntityManager(string $entityFqcn): ObjectManager
    {
        if (null === $entityManager = $this->doctrine->getManagerForClass($entityFqcn)) {
            throw new \RuntimeException(sprintf('There is no Doctrine Entity Manager defined for the "%s" class', $entityFqcn));
        }

        return $entityManager;
    }

    private function getEntityMetadata(string $entityFqcn): ClassMetadata
    {
        $entityManager = $this->getEntityManager($entityFqcn);
        $entityMetadata = $entityManager->getClassMetadata($entityFqcn);

        if (1 !== \count($entityMetadata->getIdentifierFieldNames())) {
            throw new \RuntimeException(sprintf('EasyAdmin does not support Doctrine entities with composite primary keys (such as the ones used in the "%s" entity).', $entityFqcn));
        }

        return $entityMetadata;
    }

    /**
     * @return object|null
     */
    private function getEntityInstance(string $entityFqcn, $entityIdValue)
    {
        $entityManager = $this->getEntityManager($entityFqcn);
        if (null === $entityInstance = $entityManager->getRepository($entityFqcn)->find($entityIdValue)) {
            $entityIdName = $entityManager->getClassMetadata($entityFqcn)->getIdentifierFieldNames()[0];

            throw new EntityNotFoundException(['entity_name' => $entityFqcn, 'entity_id_name' => $entityIdName, 'entity_id_value' => $entityIdValue]);
        }

        return $entityInstance;
    }
}
