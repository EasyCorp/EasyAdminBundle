<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Crud;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Crud\DefaultCategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\CategoryFactory;
use function Zenstruck\Foundry\Persistence\delete;

/**
 * Tests for default detail page behavior in EasyAdmin CRUD operations.
 */
class DetailTest extends AbstractCrudTestCase
{
    protected function getControllerFqcn(): string
    {
        return DefaultCategoryCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();
    }

    public function testDetailPageLoadsForEntity(): void
    {
        $entity = CategoryFactory::createOne();

        $this->client->request('GET', $this->generateDetailUrl($entity->getId()));

        $this->assertResponseIsSuccessful();
    }

    public function testDetailPageShowsFieldGroups(): void
    {
        $entity = CategoryFactory::createOne();

        $crawler = $this->client->request('GET', $this->generateDetailUrl($entity->getId()));

        $this->assertResponseIsSuccessful();

        // check for field groups (EasyAdmin displays fields in field-group containers)
        $this->assertSelectorExists('.content-body', 'Content body should be present');
        $this->assertSelectorExists('.field-group', 'Field groups should be present');
    }

    public function testAllFieldsAreDisplayed(): void
    {
        $entity = CategoryFactory::createOne();

        $crawler = $this->client->request('GET', $this->generateDetailUrl($entity->getId()));

        $this->assertResponseIsSuccessful();

        // get all field groups
        $fieldGroups = $crawler->filter('.content-body .field-group');
        $this->assertGreaterThan(0, $fieldGroups->count(), 'At least one field should be displayed');

        // verify field labels exist
        $fieldLabels = $crawler->filter('.field-label');
        $this->assertGreaterThan(0, $fieldLabels->count(), 'Field labels should be present');

        // verify field values exist
        $fieldValues = $crawler->filter('.field-value');
        $this->assertGreaterThan(0, $fieldValues->count(), 'Field values should be present');
    }

    public function testDetailPageShowsCorrectEntityData(): void
    {
        $entity = CategoryFactory::createOne(['name' => 'Category A']);

        $crawler = $this->client->request('GET', $this->generateDetailUrl($entity->getId()));

        $this->assertResponseIsSuccessful();

        // the page content should contain the entity's name
        $this->assertSelectorTextContains('.content-body', 'Category A');
    }

    public function testBackActionIsAvailable(): void
    {
        $entity = CategoryFactory::createOne();

        $crawler = $this->client->request('GET', $this->generateDetailUrl($entity->getId()));

        $this->assertResponseIsSuccessful();

        // check for back/index action
        $backAction = $crawler->filter('.action-'.Action::INDEX);
        $this->assertCount(1, $backAction, 'Back/Index action should be available');
    }

    public function testEditActionIsAvailable(): void
    {
        $entity = CategoryFactory::createOne();

        $crawler = $this->client->request('GET', $this->generateDetailUrl($entity->getId()));

        $this->assertResponseIsSuccessful();

        // check for edit action
        $editAction = $crawler->filter('.action-'.Action::EDIT);
        $this->assertCount(1, $editAction, 'Edit action should be available');
    }

    public function testDeleteActionIsAvailable(): void
    {
        $entity = CategoryFactory::createOne();

        $crawler = $this->client->request('GET', $this->generateDetailUrl($entity->getId()));

        $this->assertResponseIsSuccessful();

        // check for delete action
        $deleteAction = $crawler->filter('.action-'.Action::DELETE);
        $this->assertCount(1, $deleteAction, 'Delete action should be available');
    }

    public function testEditActionLinkWorks(): void
    {
        $entity = CategoryFactory::createOne();

        $crawler = $this->client->request('GET', $this->generateDetailUrl($entity->getId()));

        $this->assertResponseIsSuccessful();

        // click the edit action
        $editLink = $crawler->filter('.action-'.Action::EDIT);
        $this->assertCount(1, $editLink);

        $this->client->click($editLink->link());
        $this->assertResponseIsSuccessful();

        // verify we are on the edit form page
        $this->assertSelectorExists('form', 'Edit page should display a form');
    }

    public function testBackActionLinkWorks(): void
    {
        $entity = CategoryFactory::createOne();

        $crawler = $this->client->request('GET', $this->generateDetailUrl($entity->getId()));

        $this->assertResponseIsSuccessful();

        // click the back action
        $backLink = $crawler->filter('.action-'.Action::INDEX);
        $this->assertCount(1, $backLink);

        $this->client->click($backLink->link());
        $this->assertResponseIsSuccessful();

        // verify we are back on the index page
        $this->assertSelectorExists('.datagrid', 'Should be redirected to index page');
    }

    public function testDetailPageForNonExistentEntityReturns404(): void
    {
        $this->client->catchExceptions(true);

        $deletedEntity = CategoryFactory::createOne();
        $deletedEntityId = $deletedEntity->getId();
        delete($deletedEntity);

        $this->client->request('GET', $this->generateDetailUrl($deletedEntityId));

        $this->assertResponseStatusCodeSame(404);
        $this->assertSelectorTextContains('.error-message', 'This item is no longer available.');
    }

    public function testIdFieldIsDisplayed(): void
    {
        $entity = CategoryFactory::createOne();

        $crawler = $this->client->request('GET', $this->generateDetailUrl($entity->getId()));

        $this->assertResponseIsSuccessful();

        // the ID value should be displayed on the page
        $pageContent = $crawler->filter('.content-body')->text();
        $this->assertStringContainsString((string) $entity->getId(), $pageContent);
    }

    public function testBooleanFieldIsDisplayedCorrectly(): void
    {
        $entity = CategoryFactory::createOne(['active' => true]);

        $crawler = $this->client->request('GET', $this->generateDetailUrl($entity->getId()));

        $this->assertResponseIsSuccessful();

        // boolean fields are typically displayed as Yes/No in EasyAdmin
        $pageContent = $crawler->filter('.content-body')->text();
        $this->assertStringContainsString('Yes', $pageContent);
    }
}
