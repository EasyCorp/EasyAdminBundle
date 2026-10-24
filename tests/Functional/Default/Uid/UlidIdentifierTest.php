<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Uid;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\UlidTestEntityCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\UlidTestEntity;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\UlidTestEntityFactory;

class UlidIdentifierTest extends UidIdentifierTestCase
{
    protected function getControllerFqcn(): string
    {
        return UlidTestEntityCrudController::class;
    }

    protected function getEntityFqcn(): string
    {
        return UlidTestEntity::class;
    }

    protected function createEntity(string $name): UlidTestEntity
    {
        return UlidTestEntityFactory::createOne(['name' => $name]);
    }
}
