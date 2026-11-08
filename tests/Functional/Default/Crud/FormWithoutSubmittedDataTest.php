<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Crud;

use Doctrine\ORM\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\FormWithoutSubmittedDataCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\FormWithoutSubmittedDataWithCsrfCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\FormTestEntity;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\FormTestEntityFactory;
use PHPUnit\Framework\Attributes\TestWith;

/**
 * Tests forms whose submission sends no data under the form name, because CSRF
 * protection is disabled and every field is either disabled or an unchecked
 * checkbox (see #7819).
 */
class FormWithoutSubmittedDataTest extends AbstractCrudTestCase
{
    /** @var EntityRepository<FormTestEntity> */
    private EntityRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();
        $this->repository = $this->entityManager->getRepository(FormTestEntity::class);
    }

    protected function getControllerFqcn(): string
    {
        return FormWithoutSubmittedDataCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    #[TestWith([Action::SAVE_AND_RETURN])]
    #[TestWith([Action::SAVE_AND_CONTINUE])]
    public function testUncheckingTheOnlyEditableCheckboxUpdatesTheEntity(string $saveAction): void
    {
        $entityId = FormTestEntityFactory::createOne(['name' => 'Entity 1', 'isActive' => true])->getId();

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($entityId));
        $form = $crawler->filter(sprintf('button[name="ea[newForm][btn]"][value="%s"]', $saveAction))->form();
        $form[$this->getFormEntity().'[isActive]']->untick();

        $this->client->submit($form);

        static::assertResponseIsSuccessful();
        $this->entityManager->clear();
        $entity = $this->repository->find($entityId);
        static::assertFalse($entity->getIsActive());
        static::assertSame('Entity 1', $entity->getName());
    }

    #[TestWith([Action::SAVE_AND_RETURN])]
    #[TestWith([Action::SAVE_AND_CONTINUE])]
    #[TestWith([Action::SAVE_AND_ADD_ANOTHER])]
    public function testUncheckingTheOnlyEditableCheckboxCreatesTheEntity(string $saveAction): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());
        $form = $crawler->filter(sprintf('button[name="ea[newForm][btn]"][value="%s"]', $saveAction))->form();
        $form[$this->getFormEntity().'[isActive]']->untick();

        $this->client->submit($form);

        static::assertResponseIsSuccessful();
        $this->entityManager->clear();
        $entities = $this->repository->findAll();
        static::assertCount(1, $entities);
        static::assertFalse($entities[0]->getIsActive());
        static::assertSame('Entity 1', $entities[0]->getName());
    }

    public function testRequestWithoutTheSaveButtonDoesNotSubmitTheForm(): void
    {
        $entityId = FormTestEntityFactory::createOne(['name' => 'Entity 1', 'isActive' => true])->getId();

        $this->client->request('POST', $this->generateEditFormUrl($entityId));

        $this->entityManager->clear();
        static::assertTrue($this->repository->find($entityId)->getIsActive());
    }

    public function testSaveButtonDoesNotBypassTheCsrfProtection(): void
    {
        $entityId = FormTestEntityFactory::createOne(['name' => 'Entity 1', 'isActive' => true])->getId();

        $this->client->request(
            'POST',
            $this->generateEditFormUrl($entityId, controllerFqcn: FormWithoutSubmittedDataWithCsrfCrudController::class),
            ['ea' => ['newForm' => ['btn' => Action::SAVE_AND_RETURN]]],
        );

        $this->entityManager->clear();
        static::assertTrue($this->repository->find($entityId)->getIsActive());
    }
}
