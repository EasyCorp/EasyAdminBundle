<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Factory;

use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Factory\EntityFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Entity\EntityFactory\Address;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Entity\EntityFactory\Car;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Entity\EntityFactory\ProjectCategory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class EntityFactoryTest extends KernelTestCase
{
    private EntityFactory $entityFactory;
    protected EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        /** @var EntityFactory $entityFactory */
        $entityFactory = static::getContainer()->get(EntityFactory::class);
        $this->entityFactory = $entityFactory;

        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->entityManager = $entityManager;
    }

    public function testCreateWithoutInstance(): void
    {
        $entityDto = $this->entityFactory->create(ProjectCategory::class);
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
        $this->expectExceptionMessage(sprintf('EasyAdmin does not support Doctrine entities with composite primary keys (such as the ones used in the "%s" entity).', Car::class));
        $this->entityFactory->create(Car::class);
    }

    public function testCreateFromEntityId(): void
    {
        $project = (new ProjectCategory())->setName('foo');
        $this->entityManager->persist($project);
        $this->entityManager->flush();

        $entityDto = $this->entityFactory->create(ProjectCategory::class, $project->getId());

        $this->assertInstanceOf(ProjectCategory::class, $entityDto->getInstance());
        $this->assertSame($project->getId(), $entityDto->getInstance()->getId());
    }

    public function testCreateFromEntityInstance(): void
    {
        $project = (new ProjectCategory())->setName('foo');
        $this->entityManager->persist($project);
        $this->entityManager->flush();

        $entityDto = $this->entityFactory->createForEntityInstance($project);

        $this->assertInstanceOf(ProjectCategory::class, $entityDto->getInstance());
        $this->assertSame($project->getId(), $entityDto->getInstance()->getId());
    }
}
