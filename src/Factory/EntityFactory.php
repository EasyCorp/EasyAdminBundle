<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Factory;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\Proxy;
use Doctrine\Common\Util\ClassUtils;
use EasyCorp\Bundle\EasyAdminBundle\Collection\EntityDtoCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Entity;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\ActionsDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityBuiltEvent;
use EasyCorp\Bundle\EasyAdminBundle\Exception\EntityNotFoundException;
use EasyCorp\Bundle\EasyAdminBundle\Security\Permission;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class EntityFactory
{
    private $fieldFactory;
    private $actionFactory;
    private $authorizationChecker;
    private $doctrine;
    private $eventDispatcher;

    public function __construct(FieldFactory $fieldFactory, ActionFactory $actionFactory, AuthorizationCheckerInterface $authorizationChecker, ManagerRegistry $doctrine, EventDispatcherInterface $eventDispatcher)
    {
        $this->fieldFactory = $fieldFactory;
        $this->actionFactory = $actionFactory;
        $this->authorizationChecker = $authorizationChecker;
        $this->doctrine = $doctrine;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function processFields(EntityDto $entityDto, FieldCollection $fields): void
    {
        $this->fieldFactory->processFields($entityDto, $fields);
    }

    public function processActions(EntityDto $entityDto, ActionsDto $actionsDto): void
    {
        $this->actionFactory->processActions($entityDto, $actionsDto);
    }

    /**
     * @param FieldInterface[] $fields
     */
    public function create(Entity $entity, iterable $fields = null, ActionsDto $actionsDto = null): EntityDto
    {
        return $this->doCreate(null, $entity->getFqcn(), $entity->getId(), $entity->getPermission(), $fields, $actionsDto);
    }

    public function createForEntityInstance($entityInstance): EntityDto
    {
        return $this->doCreate($entityInstance);
    }

    public function createForEntityFqcn(string $entityFqcn): EntityDto
    {
        return $this->doCreate(null, $entityFqcn);
    }

    /**
     * @param FieldDto[] $actionsDto
     */
    public function createAll(EntityDto $entityDto, iterable $entityInstances, iterable $configuredProperties, ActionsDto $actionsDto): EntityDtoCollection
    {
        $builtEntities = [];
        foreach ($entityInstances as $entityInstance) {
            $currentEntityDto = $entityDto->updateInstance($entityInstance);
            $currentEntityDto = $this->fieldFactory->create($currentEntityDto, $configuredProperties);
            $currentEntityDto = $this->actionFactory->createForEntity($actionsDto, $currentEntityDto);

            $builtEntities[] = $currentEntityDto;
        }

        return EntityDtoCollection::new($builtEntities);
    }

    private function doCreate($entityInstance = null, ?string $entityFqcn = null, $entityId = null, ?string $entityPermission = null, iterable $configuredProperties = null, ?ActionsDto $actionsDto = null): EntityDto
    {
        if (null === $entityInstance && null !== $entityFqcn) {
            $entityInstance = null === $entityId ? null : $this->getEntityInstance($entityFqcn, $entityId);
        }

        if (null !== $entityInstance && null === $entityFqcn) {
            if ($entityInstance instanceof Proxy) {
                $entityInstance->__load();
            }

            $entityFqcn = ClassUtils::getClass($entityInstance);
        }

        $entityMetadata = $this->getEntityMetadata($entityFqcn);
        $entityDto = new EntityDto($entityFqcn, $entityMetadata, $entityPermission, $entityInstance);

        if (!$this->authorizationChecker->isGranted(Permission::EA_VIEW_ENTITY, $entityDto)) {
            $entityDto->markAsInaccessible();
        } else {
            if (null !== $configuredProperties) {
                $entityDto = $this->fieldFactory->create($entityDto, $configuredProperties);
            }

            if (null !== $actionsDto) {
                $entityDto = $this->actionFactory->createForEntity($actionsDto, $entityDto);
            }
        }

        $this->eventDispatcher->dispatch(new AfterEntityBuiltEvent($entityDto));

        return $entityDto;
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
