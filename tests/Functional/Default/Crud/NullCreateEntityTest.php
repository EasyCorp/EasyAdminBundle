<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Crud;

use Doctrine\ORM\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\ConstructorArgsTestEntityCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\NullEmptyDataTestEntityCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\ConstructorArgsTestEntity;

/**
 * Tests CRUD controllers whose createEntity() returns null, so the "new" form
 * creates the entity with its "empty_data" option (see #6957).
 */
class NullCreateEntityTest extends AbstractCrudTestCase
{
    /** @var EntityRepository<ConstructorArgsTestEntity> */
    private EntityRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();
        $this->repository = $this->entityManager->getRepository(ConstructorArgsTestEntity::class);
    }

    protected function getControllerFqcn(): string
    {
        return ConstructorArgsTestEntityCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    public function testNewFormRendersWithoutEntityInstance(): void
    {
        $this->client->request('GET', $this->generateNewFormUrl());

        static::assertResponseIsSuccessful();
        $this->assertFormFieldExists('name');
    }

    public function testNewFormCreatesTheEntityWithEmptyData(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());
        $form = $crawler->filter($this->getEntityFormSelector())->form();
        $form[$this->getFormEntity().'[name]'] = 'Created with empty_data';

        $this->client->submit($form);

        static::assertResponseIsSuccessful();
        $this->entityManager->clear();
        static::assertNotNull($this->repository->findOneBy(['name' => 'Created with empty_data']));
    }

    public function testNewFormWithoutEmptyDataThrowsAnExplicitError(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl(controllerFqcn: NullEmptyDataTestEntityCrudController::class));
        $form = $crawler->filter($this->getEntityFormSelector())->form();
        $form[$this->getFormEntity().'[name]'] = 'Never persisted';

        $this->client->catchExceptions(false);
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('define the "empty_data" option of the form');

        $this->client->submit($form);
    }
}
