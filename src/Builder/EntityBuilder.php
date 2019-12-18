<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Builder;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\ObjectManager;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityBuiltEvent;
use EasyCorp\Bundle\EasyAdminBundle\Exception\EntityNotFoundException;
use EasyCorp\Bundle\EasyAdminBundle\Security\Permission;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class EntityBuilder
{
    private $authorizationChecker;
    private $doctrine;
    private $eventDispatcher;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker, ManagerRegistry $doctrine, EventDispatcherInterface $eventDispatcher)
    {
        $this->authorizationChecker = $authorizationChecker;
        $this->doctrine = $doctrine;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function build(string $entityFqcn, ?string $entityPermission, $entityId = null): EntityDto
    {
        $entityMetadata = $this->getEntityMetadata($entityFqcn);
        $entityInstance = null === $entityId ? null : $this->getEntityInstance($entityFqcn, $entityId);
        $entityDto = new EntityDto($entityFqcn, $entityMetadata, $entityPermission, $entityInstance, $entityId);

        $this->checkEntityPermission($entityDto);
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

    private function checkEntityPermission(EntityDto $entityDto): void
    {
        if (!$this->authorizationChecker->isGranted(Permission::EA_VIEW_ENTITY, $entityDto)) {
            $entityDto->markAsInaccessible();
        }
    }
}
