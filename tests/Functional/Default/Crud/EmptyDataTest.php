<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Crud;

use Doctrine\ORM\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\DefaultCrudTestEntityCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\DefaultCrudTestEntity;

/**
 * Tests that submitting an empty value for a text-based field doesn't pass NULL to the entity.
 * DefaultCrudTestEntity::$name is mapped to a non-nullable column, has a NotBlank constraint and a
 * setName(string $name) setter, so a NULL value makes the form fail with a TypeError before the
 * validation error can be displayed (see https://github.com/EasyCorp/EasyAdminBundle/issues/7797).
 */
class EmptyDataTest extends AbstractCrudTestCase
{
    protected EntityRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();
        $this->repository = $this->entityManager->getRepository(DefaultCrudTestEntity::class);
    }

    protected function getControllerFqcn(): string
    {
        return DefaultCrudTestEntityCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    public function testEmptyNonNullableTextFieldDisplaysTheValidationError(): void
    {
        $entity = $this->repository->findOneBy([]);
        $crawler = $this->client->request('GET', $this->generateEditFormUrl($entity->getId()));
        $form = $crawler->filter($this->getEntityFormSelector())->form();
        $formName = $this->getFormEntity();
        $form[$formName.'[name]'] = '';

        $this->client->submit($form);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorTextContains('.invalid-feedback', 'This value should not be blank.');
    }

    public function testEmptyNullableTextFieldIsStillStoredAsNull(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());
        $form = $crawler->filter($this->getEntityFormSelector())->form();
        $formName = $this->getFormEntity();
        // 'name' can't be left empty because of its NotBlank constraint, but 'description' can
        $form[$formName.'[name]'] = 'Entity with an empty description';
        $form[$formName.'[description]'] = '';

        $this->client->submit($form);

        $this->assertResponseIsSuccessful();

        $this->entityManager->clear();
        $entity = $this->repository->findOneBy(['name' => 'Entity with an empty description']);

        // 'description' is mapped to a nullable column, so EasyAdmin doesn't change its 'empty_data'
        $this->assertNull($entity->getDescription());
    }
}
