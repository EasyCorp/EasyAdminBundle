<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Factory;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\Proxy;
use EasyCorp\Bundle\EasyAdminBundle\Collection\ActionCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\EntityCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Dto\ActionConfigDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityBuiltEvent;
use EasyCorp\Bundle\EasyAdminBundle\Exception\EntityNotFoundException;
use EasyCorp\Bundle\EasyAdminBundle\Security\Permission;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class EntityFactory
{
    public function __construct(
        private FieldFactory|AuthorizationCheckerInterface|null $fieldFactory,
        private ActionFactory|ManagerRegistry|null $actionFactory,
        private AuthorizationCheckerInterface|EventDispatcherInterface $authorizationChecker,
        private ?ManagerRegistry $doctrine = null,
        private ?EventDispatcherInterface $eventDispatcher = null,
    ) {
        if ($this->fieldFactory instanceof FieldFactory) {
            trigger_deprecation(
                'easycorp/easyadmin-bundle',
                '4.27.0',
                'Passing the arguments "$fieldFactory" and "$actionFactory" to the "%s" constructor is deprecated.',
                self::class
            );
        } else {
            $this->fieldFactory = null;
            $this->actionFactory = null;
            $this->authorizationChecker = $fieldFactory;
            $this->doctrine = $actionFactory;
            $this->eventDispatcher = $authorizationChecker;
        }
    }

    /**
     * @deprecated since 4.27.0 and it will be removed in EasyAdmin 5.0.0. Use FieldFactory::processFields() instead
     */
    public function processFields(EntityDto $entityDto, FieldCollection $fields, ?string $pageName = null): void
    {
        $this->fieldFactory->processFields($entityDto, $fields, $pageName);
    }

    /**
     * @deprecated since 4.27.0 and it will be removed in EasyAdmin 5.0.0. Use FieldFactory::processFieldsForAll() instead
     */
    public function processFieldsForAll(EntityCollection $entities, FieldCollection $fields, ?string $pageName = null): void
    {
        $this->fieldFactory->processFieldsForAll($entities, $fields);
    }

    /**
     * @deprecated since 4.27.0 and it will be removed in EasyAdmin 5.0.0. Use ActionFactory::processEntityActions() instead
     */
    public function processActions(EntityDto $entityDto, ActionConfigDto $actionConfigDto): void
    {
        $this->actionFactory->processEntityActions($entityDto, $actionConfigDto);
    }

    /**
     * @deprecated since 4.27.0 and it will be removed in EasyAdmin 5.0.0. Use ActionFactory::processGlobalActionsAndEntityActionsForAll() instead
     */
    public function processActionsForAll(EntityCollection $entities, ActionConfigDto $actionConfigDto): ActionCollection
    {
        return $this->actionFactory->processGlobalActionsAndEntityActionsForAll($entities, $actionConfigDto);
    }

    /**
     * @param class-string $entityFqcn
     */
    public function create(string $entityFqcn, mixed $entityId = null, string|Expression|null $entityPermission = null): EntityDto
    {
        return $this->doCreate($entityFqcn, $entityId, $entityPermission);
    }

    /**
     * @param object $entityInstance
     */
    public function createForEntityInstance(/* object */ $entityInstance): EntityDto
    {
        if (!\is_object($entityInstance)) {
            trigger_deprecation(
                'easycorp/easyadmin-bundle',
                '4.27.0',
                'Not passing argument "$entityInstance" for method "%s" of type "object" is deprecated.',
                __METHOD__,
            );
        }

        return $this->doCreate(null, null, null, $entityInstance);
    }

    /**
     * @param iterable<object>|null $entityInstances
     */
    public function createCollection(EntityDto $entityDto, ?iterable $entityInstances): EntityCollection
    {
        $entityDtos = [];

        foreach ($entityInstances as $entityInstance) {
            $newEntityDto = $entityDto->newWithInstance($entityInstance);
            $newEntityId = $newEntityDto->getPrimaryKeyValueAsString();
            if (!$this->authorizationChecker->isGranted(Permission::EA_ACCESS_ENTITY, $newEntityDto)) {
                $newEntityDto->markAsInaccessible();
            }

            $entityDtos[$newEntityId] = $newEntityDto;
        }

        return EntityCollection::new($entityDtos);
    }

    /**
     * @template TEntity of object
     *
     * @param class-string<TEntity> $entityFqcn
     *
     * @return ClassMetadata<TEntity>
     */
    public function getEntityMetadata(string $entityFqcn): ClassMetadata
    {
        $entityManager = $this->getEntityManager($entityFqcn);
        /** @var ClassMetadata<TEntity> $entityMetadata */
        $entityMetadata = $entityManager->getClassMetadata($entityFqcn);

        if ($entityMetadata->isIdentifierComposite) {
            throw new \RuntimeException(sprintf('EasyAdmin does not support Doctrine entities with composite primary keys (such as the ones used in the "%s" entity).', $entityFqcn));
        }

        return $entityMetadata;
    }

    /**
     * @param class-string|null $entityFqcn
     */
    private function doCreate(?string $entityFqcn = null, mixed $entityId = null, string|Expression|null $entityPermission = null, ?object $entityInstance = null): EntityDto
    {
        if (null === $entityInstance && null !== $entityFqcn) {
            $entityInstance = null === $entityId ? null : $this->getEntityInstance($entityFqcn, $entityId);
        }

        if (null !== $entityInstance && null === $entityFqcn) {
            if ($entityInstance instanceof Proxy) {
                $entityInstance->__load();
            }

            $entityFqcn = $this->getRealClass($entityInstance::class);
        }

        $entityMetadata = $this->getEntityMetadata($entityFqcn);
        $entityDto = new EntityDto($entityFqcn, $entityMetadata, $entityPermission, $entityInstance);

        if (!$this->authorizationChecker->isGranted(Permission::EA_ACCESS_ENTITY, $entityDto)) {
            $entityDto->markAsInaccessible();
        }

        $this->eventDispatcher->dispatch(new AfterEntityBuiltEvent($entityDto));

        return $entityDto;
    }

    /**
     * @param class-string $entityFqcn
     */
    private function getEntityManager(string $entityFqcn): ObjectManager
    {
        if (null === $entityManager = $this->doctrine->getManagerForClass($entityFqcn)) {
            throw new \RuntimeException(sprintf('There is no Doctrine Entity Manager defined for the "%s" class', $entityFqcn));
        }

        return $entityManager;
    }

    /**
     * @param class-string $entityFqcn
     */
    private function getEntityInstance(string $entityFqcn, mixed $entityIdValue): object
    {
        $entityManager = $this->getEntityManager($entityFqcn);
        if (null === $entityInstance = $entityManager->getRepository($entityFqcn)->find($entityIdValue)) {
            $entityIdName = $entityManager->getClassMetadata($entityFqcn)->getIdentifierFieldNames()[0];

            throw new EntityNotFoundException(['entity_name' => $entityFqcn, 'entity_id_name' => $entityIdName, 'entity_id_value' => $entityIdValue]);
        }

        return $entityInstance;
    }

    /**
     * Code copied from Symfony\Bridge\Doctrine\Form\DoctrineOrmTypeGuesser
     * because Doctrine ORM 3.x removed the ClassUtil class where this method was defined
     * (c) Fabien Potencier <fabien@symfony.com> - MIT License.
     *
     * @param class-string $class
     *
     * @return class-string
     */
    private function getRealClass(string $class): string
    {
        if (false === $pos = strrpos($class, '\\'.Proxy::MARKER.'\\')) {
            return $class;
        }

        /** @var class-string $realClass */
        $realClass = substr($class, $pos + Proxy::MARKER_LENGTH + 2);

        return $realClass;
    }
}
