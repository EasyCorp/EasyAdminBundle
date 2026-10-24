<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Uid;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\UuidTestEntityCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\UuidTestEntity;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\UuidTestEntityFactory;

class UuidIdentifierTest extends UidIdentifierTestCase
{
    protected function getControllerFqcn(): string
    {
        return UuidTestEntityCrudController::class;
    }

    protected function getEntityFqcn(): string
    {
        return UuidTestEntity::class;
    }

    protected function createEntity(string $name): UuidTestEntity
    {
        return UuidTestEntityFactory::createOne(['name' => $name]);
    }
}
