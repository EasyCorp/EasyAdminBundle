<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Factory;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\Proxy;
use EasyCorp\Bundle\EasyAdminBundle\Collection\ActionCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\EntityCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Dto\ActionConfigDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\ActionConfigDtoInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDtoInterface;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityBuiltEvent;
use EasyCorp\Bundle\EasyAdminBundle\Exception\EntityNotFoundException;
use EasyCorp\Bundle\EasyAdminBundle\Security\Permission;
use EasyCorp\Bundle\EasyAdminBundle\Security\PermissionInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class EntityFactory implements EntityFactoryInterface
{
    private FieldFactory $fieldFactory;
    private ActionFactory $actionFactory;
    private AuthorizationCheckerInterface $authorizationChecker;
    private ManagerRegistry $doctrine;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(FieldFactoryInterface $fieldFactory, ActionFactoryInterface $actionFactory, AuthorizationCheckerInterface $authorizationChecker, ManagerRegistry $doctrine, EventDispatcherInterface $eventDispatcher)
    {
        $this->fieldFactory = $fieldFactory;
        $this->actionFactory = $actionFactory;
        $this->authorizationChecker = $authorizationChecker;
        $this->doctrine = $doctrine;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function processFields(EntityDtoInterface $entityDto, FieldCollection $fields): void
    {
        $this->fieldFactory->processFields($entityDto, $fields);
    }

    public function processFieldsForAll(EntityCollection $entities, FieldCollection $fields): void
    {
        foreach ($entities as $entity) {
            $this->processFields($entity, clone $fields);
            $entities->set($entity);
        }
    }

    public function processActions(EntityDtoInterface $entityDto, ActionConfigDtoInterface $actionConfigDto): void
    {
        $this->actionFactory->processEntityActions($entityDto, $actionConfigDto);
    }

    public function processActionsForAll(EntityCollection $entities, ActionConfigDtoInterface $actionConfigDto): ActionCollection
    {
        foreach ($entities as $entity) {
            $this->processActions($entity, clone $actionConfigDto);
        }

        return $this->actionFactory->processGlobalActions($actionConfigDto);
    }

    public function create(string $entityFqcn, $entityId = null, ?string $entityPermission = null): EntityDtoInterface
    {
        return $this->doCreate($entityFqcn, $entityId, $entityPermission);
    }

    public function createForEntityInstance($entityInstance): EntityDtoInterface
    {
        return $this->doCreate(null, null, null, $entityInstance);
    }

    public function createCollection(EntityDtoInterface $entityDto, ?iterable $entityInstances): EntityCollection
    {
        $entityDtos = [];

        foreach ($entityInstances as $entityInstance) {
            $newEntityDto = $entityDto->newWithInstance($entityInstance);
            $newEntityId = $newEntityDto->getPrimaryKeyValueAsString();
            if (!$this->authorizationChecker->isGranted(PermissionInterface::EA_ACCESS_ENTITY, $newEntityDto)) {
                $newEntityDto->markAsInaccessible();
            }

            $entityDtos[$newEntityId] = $newEntityDto;
        }

        return EntityCollection::new($entityDtos);
    }

    public function getEntityMetadata(string $entityFqcn): ClassMetadata
    {
        $entityManager = $this->getEntityManager($entityFqcn);
        /** @var ClassMetadata&ClassMetadataInfo $entityMetadata */
        $entityMetadata = $entityManager->getClassMetadata($entityFqcn);

        if (1 !== \count($entityMetadata->getIdentifierFieldNames())) {
            throw new \RuntimeException(sprintf('EasyAdmin does not support Doctrine entities with composite primary keys (such as the ones used in the "%s" entity).', $entityFqcn));
        }

        return $entityMetadata;
    }

    private function doCreate(?string $entityFqcn = null, $entityId = null, ?string $entityPermission = null, $entityInstance = null): EntityDtoInterface
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

        if (!$this->authorizationChecker->isGranted(PermissionInterface::EA_ACCESS_ENTITY, $entityDto)) {
            $entityDto->markAsInaccessible();
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

    private function getEntityInstance(string $entityFqcn, $entityIdValue): object
    {
        $entityManager = $this->getEntityManager($entityFqcn);
        if (null === $entityInstance = $entityManager->getRepository($entityFqcn)->find($entityIdValue)) {
            $entityIdName = $entityManager->getClassMetadata($entityFqcn)->getIdentifierFieldNames()[0];

            throw new EntityNotFoundException([
                'entity_name' => $entityFqcn,
                'entity_id_name' => $entityIdName,
                'entity_id_value' => $entityIdValue
            ]);
        }

        return $entityInstance;
    }
}
