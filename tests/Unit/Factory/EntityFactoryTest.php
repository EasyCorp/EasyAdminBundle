<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Factory;

use EasyCorp\Bundle\EasyAdminBundle\Factory\EntityFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Address;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Category;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\CompositeKeyTestEntity;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\CategoryFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class EntityFactoryTest extends KernelTestCase
{
    private EntityFactory $entityFactory;

    protected function setUp(): void
    {
        /** @var EntityFactory $entityFactory */
        $entityFactory = static::getContainer()->get(EntityFactory::class);
        $this->entityFactory = $entityFactory;
    }

    public function testCreateWithoutInstance(): void
    {
        $entityDto = $this->entityFactory->create(Category::class);
        $this->assertNull($entityDto->getInstance());
    }

    public function testCreateFailsOnUnmanagedClass(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(sprintf('There is no Doctrine Entity Manager defined for the "%s" class', Address::class));
        $this->entityFactory->create(Address::class);
    }

    public function testCreateFailsOnCompositePrimaryKeys(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(sprintf('EasyAdmin does not support Doctrine entities with composite primary keys (such as the ones used in the "%s" entity).', CompositeKeyTestEntity::class));
        $this->entityFactory->create(CompositeKeyTestEntity::class);
    }

    public function testCreateFromEntityId(): void
    {
        $category = CategoryFactory::createOne();

        $entityDto = $this->entityFactory->create(Category::class, $category->getId());

        $this->assertInstanceOf(Category::class, $entityDto->getInstance());
        $this->assertSame($category->getId(), $entityDto->getInstance()->getId());
    }

    public function testCreateFromEntityInstance(): void
    {
        $category = CategoryFactory::createOne();

        $entityDto = $this->entityFactory->createForEntityInstance($category);

        $this->assertInstanceOf(Category::class, $entityDto->getInstance());
        $this->assertSame($category->getId(), $entityDto->getInstance()->getId());
    }
}
