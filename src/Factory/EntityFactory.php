<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Factory;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\Proxy;
use Doctrine\Common\Util\ClassUtils;
use EasyCorp\Bundle\EasyAdminBundle\Collection\EntityDtoCollection;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\Action;
use EasyCorp\Bundle\EasyAdminBundle\Context\ApplicationContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfigInterface;
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
    private $actionFactory;
    private $authorizationChecker;
    private $doctrine;
    private $eventDispatcher;

    public function __construct(ApplicationContextProvider $applicationContextProvider, PropertyFactory $propertyFactory, ActionFactory $actionFactory, AuthorizationCheckerInterface $authorizationChecker, ManagerRegistry $doctrine, EventDispatcherInterface $eventDispatcher)
    {
        $this->applicationContextProvider = $applicationContextProvider;
        $this->propertyFactory = $propertyFactory;
        $this->actionFactory = $actionFactory;
        $this->authorizationChecker = $authorizationChecker;
        $this->doctrine = $doctrine;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param PropertyConfigInterface[] $configuredProperties
     * @param Action[]                  $configuredActions
     */
    public function create(iterable $configuredProperties = null, array $configuredActions = null): EntityDto
    {
        $applicationContext = $this->applicationContextProvider->getContext();
        $entityFqcn = $applicationContext->getCrud()->getEntityFqcn();
        $entityId = $applicationContext->getRequest()->query->get('entityId');
        $entityPermission = $applicationContext->getCrud()->getPage()->getEntityPermission();

        return $this->doCreate(null, $entityFqcn, $entityId, $entityPermission, $configuredProperties, $configuredActions);
    }

    public function createForEntityInstance($entityInstance): EntityDto
    {
        return $this->doCreate($entityInstance);
    }

    /**
     * @param PropertyConfigInterface[] $propertiesConfig
     * @param Action[]                  $configuredActions
     */
    public function createAll(EntityDto $entityDto, iterable $entityInstances, iterable $configuredProperties, array $configuredActions): EntityDtoCollection
    {
        $builtEntities = [];
        foreach ($entityInstances as $entityInstance) {
            $currentEntityDto = $entityDto->updateInstance($entityInstance);
            $currentEntityDto = $this->propertyFactory->create($currentEntityDto, $configuredProperties);
            $currentEntityDto = $this->actionFactory->create($currentEntityDto, $configuredActions);

            $builtEntities[] = $currentEntityDto;
        }

        return EntityDtoCollection::new($builtEntities);
    }

    private function doCreate($entityInstance = null, ?string $entityFqcn = null, $entityId = null, ?string $entityPermission = null, iterable $configuredProperties = null, array $configuredActions = null): EntityDto
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
                $entityDto = $this->propertyFactory->create($entityDto, $configuredProperties);
            }

            if (null !== $configuredActions) {
                $entityDto = $this->actionFactory->create($entityDto, $configuredActions);
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
