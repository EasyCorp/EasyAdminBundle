<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Actions;

use Doctrine\ORM\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\ActionTestEntityCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\ActionTestEntity;

/**
 * Tests for entity action features including displayIf(), custom actions,
 * render modes, and HTML attributes.
 */
class EntityActionsTest extends AbstractCrudTestCase
{
    protected EntityRepository $actionTestEntities;

    protected function getControllerFqcn(): string
    {
        return ActionTestEntityCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();

        $this->actionTestEntities = $this->entityManager->getRepository(ActionTestEntity::class);
    }

    // ============================================
    // displayIf() Tests
    // ============================================

    public function testDisplayIfShowsActivateForInactiveEntity(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        // Find an inactive entity row and verify activate action is shown
        $inactiveEntity = $this->actionTestEntities->findOneBy(['isActive' => false]);
        static::assertNotNull($inactiveEntity, 'Test requires at least one inactive entity');

        $row = $crawler->filter(sprintf('tr[data-id="%s"]', $inactiveEntity->getId()));
        static::assertCount(1, $row, 'Entity row should exist');

        // Activate action should be visible for inactive entities
        $activateAction = $row->filter('a[data-action-name="activate"]');
        static::assertCount(1, $activateAction, 'Activate action should be visible for inactive entity');

        // Deactivate action should NOT be visible for inactive entities
        $deactivateAction = $row->filter('a[data-action-name="deactivate"]');
        static::assertCount(0, $deactivateAction, 'Deactivate action should not be visible for inactive entity');
    }

    public function testDisplayIfShowsDeactivateForActiveEntity(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        // Find an active entity row and verify deactivate action is shown
        $activeEntity = $this->actionTestEntities->findOneBy(['isActive' => true]);
        static::assertNotNull($activeEntity, 'Test requires at least one active entity');

        $row = $crawler->filter(sprintf('tr[data-id="%s"]', $activeEntity->getId()));
        static::assertCount(1, $row, 'Entity row should exist');

        // Deactivate action should be visible for active entities
        $deactivateAction = $row->filter('a[data-action-name="deactivate"]');
        static::assertCount(1, $deactivateAction, 'Deactivate action should be visible for active entity');

        // Activate action should NOT be visible for active entities
        $activateAction = $row->filter('a[data-action-name="activate"]');
        static::assertCount(0, $activateAction, 'Activate action should not be visible for active entity');
    }

    public function testDisplayIfHidesDeleteForNonDeletableEntity(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        // Find a non-deletable entity row
        $nonDeletableEntity = $this->actionTestEntities->findOneBy(['isDeletable' => false]);
        static::assertNotNull($nonDeletableEntity, 'Test requires at least one non-deletable entity');

        $row = $crawler->filter(sprintf('tr[data-id="%s"]', $nonDeletableEntity->getId()));
        static::assertCount(1, $row, 'Entity row should exist');

        // Delete action should NOT be visible for non-deletable entities
        $deleteAction = $row->filter('a[data-action-name="delete"]');
        static::assertCount(0, $deleteAction, 'Delete action should not be visible for non-deletable entity');
    }

    public function testDisplayIfShowsDeleteForDeletableEntity(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        // Find a deletable entity row
        $deletableEntity = $this->actionTestEntities->findOneBy(['isDeletable' => true]);
        static::assertNotNull($deletableEntity, 'Test requires at least one deletable entity');

        $row = $crawler->filter(sprintf('tr[data-id="%s"]', $deletableEntity->getId()));
        static::assertCount(1, $row, 'Entity row should exist');

        // Delete action should be visible for deletable entities
        $deleteAction = $row->filter('a[data-action-name="delete"]');
        static::assertCount(1, $deleteAction, 'Delete action should be visible for deletable entity');
    }

    public function testDisplayIfWorksOnDetailPage(): void
    {
        // Test with an active, deletable entity
        $activeEntity = $this->actionTestEntities->findOneBy(['isActive' => true, 'isDeletable' => true]);
        static::assertNotNull($activeEntity, 'Test requires an active, deletable entity');

        $crawler = $this->client->request('GET', $this->generateDetailUrl($activeEntity->getId()));

        // Deactivate should be visible (entity is active)
        $deactivateAction = $crawler->filter('a[data-action-name="deactivate"]');
        static::assertCount(1, $deactivateAction, 'Deactivate action should be visible on detail page for active entity');

        // Activate should NOT be visible (entity is already active)
        $activateAction = $crawler->filter('a[data-action-name="activate"]');
        static::assertCount(0, $activateAction, 'Activate action should not be visible on detail page for active entity');

        // Delete should be visible (entity is deletable)
        $deleteAction = $crawler->filter('a[data-action-name="delete"]');
        static::assertCount(1, $deleteAction, 'Delete action should be visible on detail page for deletable entity');

        // Now test with an inactive, non-deletable entity
        $inactiveNonDeletableEntity = $this->actionTestEntities->findOneBy(['isActive' => false, 'isDeletable' => false]);
        static::assertNotNull($inactiveNonDeletableEntity, 'Test requires an inactive, non-deletable entity');

        $crawler = $this->client->request('GET', $this->generateDetailUrl($inactiveNonDeletableEntity->getId()));

        // Activate should be visible (entity is inactive)
        $activateAction = $crawler->filter('a[data-action-name="activate"]');
        static::assertCount(1, $activateAction, 'Activate action should be visible on detail page for inactive entity');

        // Deactivate should NOT be visible (entity is inactive)
        $deactivateAction = $crawler->filter('a[data-action-name="deactivate"]');
        static::assertCount(0, $deactivateAction, 'Deactivate action should not be visible on detail page for inactive entity');

        // Delete should NOT be visible (entity is not deletable)
        $deleteAction = $crawler->filter('a[data-action-name="delete"]');
        static::assertCount(0, $deleteAction, 'Delete action should not be visible on detail page for non-deletable entity');
    }

    // ============================================
    // Custom Action Execution Tests
    // ============================================

    public function testActivateEntityAction(): void
    {
        // Find an inactive entity to activate
        $inactiveEntity = $this->actionTestEntities->findOneBy(['isActive' => false]);
        static::assertNotNull($inactiveEntity, 'Test requires an inactive entity');
        static::assertFalse($inactiveEntity->isActive(), 'Entity should be inactive before test');

        $entityId = $inactiveEntity->getId();
        $entityName = $inactiveEntity->getName();

        // Navigate to index page and click activate
        $crawler = $this->client->request('GET', $this->generateIndexUrl());
        $row = $crawler->filter(sprintf('tr[data-id="%s"]', $entityId));
        $activateLink = $row->filter('a[data-action-name="activate"]')->link();
        $this->client->click($activateLink);

        // Verify the entity was activated
        $this->entityManager->clear();
        $updatedEntity = $this->actionTestEntities->find($entityId);
        static::assertTrue($updatedEntity->isActive(), 'Entity should be active after clicking activate action');

        // Verify flash message is shown
        $crawler = $this->client->getCrawler();
        static::assertStringContainsString(
            sprintf('Entity "%s" has been activated.', $entityName),
            $crawler->filter('.alert-success')->text()
        );
    }

    public function testDeactivateEntityAction(): void
    {
        // Find an active entity to deactivate
        $activeEntity = $this->actionTestEntities->findOneBy(['isActive' => true]);
        static::assertNotNull($activeEntity, 'Test requires an active entity');
        static::assertTrue($activeEntity->isActive(), 'Entity should be active before test');

        $entityId = $activeEntity->getId();
        $entityName = $activeEntity->getName();

        // Navigate to index page and click deactivate
        $crawler = $this->client->request('GET', $this->generateIndexUrl());
        $row = $crawler->filter(sprintf('tr[data-id="%s"]', $entityId));
        $deactivateLink = $row->filter('a[data-action-name="deactivate"]')->link();
        $this->client->click($deactivateLink);

        // Verify the entity was deactivated
        $this->entityManager->clear();
        $updatedEntity = $this->actionTestEntities->find($entityId);
        static::assertFalse($updatedEntity->isActive(), 'Entity should be inactive after clicking deactivate action');

        // Verify flash message is shown
        $crawler = $this->client->getCrawler();
        static::assertStringContainsString(
            sprintf('Entity "%s" has been deactivated.', $entityName),
            $crawler->filter('.alert-success')->text()
        );
    }

    public function testActivateActionFromDetailPage(): void
    {
        // Find an inactive entity
        $inactiveEntity = $this->actionTestEntities->findOneBy(['isActive' => false]);
        static::assertNotNull($inactiveEntity, 'Test requires an inactive entity');

        $entityId = $inactiveEntity->getId();
        $entityName = $inactiveEntity->getName();

        // Navigate to detail page and click activate
        $crawler = $this->client->request('GET', $this->generateDetailUrl($entityId));
        $activateLink = $crawler->filter('a[data-action-name="activate"]')->link();
        $this->client->click($activateLink);

        // Verify the entity was activated
        $this->entityManager->clear();
        $updatedEntity = $this->actionTestEntities->find($entityId);
        static::assertTrue($updatedEntity->isActive(), 'Entity should be active after clicking activate action from detail page');

        // Verify flash message
        $crawler = $this->client->getCrawler();
        static::assertStringContainsString(
            sprintf('Entity "%s" has been activated.', $entityName),
            $crawler->filter('.alert-success')->text()
        );
    }

    public function testDeactivateActionFromDetailPage(): void
    {
        // Find an active entity
        $activeEntity = $this->actionTestEntities->findOneBy(['isActive' => true]);
        static::assertNotNull($activeEntity, 'Test requires an active entity');

        $entityId = $activeEntity->getId();
        $entityName = $activeEntity->getName();

        // Navigate to detail page and click deactivate
        $crawler = $this->client->request('GET', $this->generateDetailUrl($entityId));
        $deactivateLink = $crawler->filter('a[data-action-name="deactivate"]')->link();
        $this->client->click($deactivateLink);

        // Verify the entity was deactivated
        $this->entityManager->clear();
        $updatedEntity = $this->actionTestEntities->find($entityId);
        static::assertFalse($updatedEntity->isActive(), 'Entity should be inactive after clicking deactivate action from detail page');

        // Verify flash message
        $crawler = $this->client->getCrawler();
        static::assertStringContainsString(
            sprintf('Entity "%s" has been deactivated.', $entityName),
            $crawler->filter('.alert-success')->text()
        );
    }

    // ============================================
    // Render Mode Tests (Global Actions)
    // ============================================

    public function testRenderAsButtonCreatesButtonElement(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        // Check for button action in the global actions area
        $buttonAction = $crawler->filter('.global-actions button[data-action-name="buttonAction"]');
        static::assertCount(1, $buttonAction, 'Button action should be rendered as a <button> element in global actions');
    }

    public function testRenderAsLinkCreatesAnchorElement(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        // Check that link action is rendered as <a> element in global actions
        $linkAction = $crawler->filter('.global-actions a[data-action-name="linkAction"]');
        static::assertCount(1, $linkAction, 'Link action should be rendered as an <a> element in global actions');
    }

    public function testRenderAsFormCreatesFormElement(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        // Check that form action is rendered inside a <form> element in global actions
        $formAction = $crawler->filter('.global-actions form.action-formAction');
        static::assertCount(1, $formAction, 'Form action should be rendered inside a <form> element in global actions');

        // Verify the form uses POST method
        static::assertSame('POST', $formAction->first()->attr('method'), 'Form action should use POST method');
    }

    public function testRenderAsFormInDropdownCreatesHiddenForm(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        // Entity actions with renderAsForm() in the dropdown should have an associated form
        // Find any entity row and look for the entityFormAction in its dropdown
        $firstEntity = $this->actionTestEntities->findOneBy([]);
        static::assertNotNull($firstEntity, 'Test requires at least one entity');

        $row = $crawler->filter(sprintf('tr[data-id="%s"]', $firstEntity->getId()));
        static::assertCount(1, $row, 'Entity row should exist');

        // The dropdown should contain a link for the entityFormAction
        // and somewhere a form element (may be inside <li> before the <a>)
        $dropdownItem = $row->filter('[data-action-name="entityFormAction"]');
        static::assertCount(1, $dropdownItem, 'Entity form action should exist in dropdown');

        // For renderAsForm() in dropdowns, a hidden form is created alongside the link
        // The form is inside the same <li> element
        $parentLi = $dropdownItem->closest('li');
        $hiddenForm = $parentLi->filter('form');
        static::assertCount(1, $hiddenForm, 'Hidden form should exist for dropdown action with renderAsForm()');
        static::assertSame('POST', $hiddenForm->attr('method'), 'Hidden form should use POST method');
    }

    // ============================================
    // HTML Attributes Tests (Entity Actions in Dropdown)
    // ============================================

    public function testSetHtmlAttributesOnAction(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        // Find action with custom HTML attributes in the first entity's dropdown
        $firstEntity = $this->actionTestEntities->findOneBy([]);
        static::assertNotNull($firstEntity, 'Test requires at least one entity');

        $row = $crawler->filter(sprintf('tr[data-id="%s"]', $firstEntity->getId()));
        $attrAction = $row->filter('[data-action-name="attrAction"]')->first();
        static::assertCount(1, $attrAction, 'Action with HTML attributes should exist');

        // Verify custom attributes are present
        static::assertSame('value', $attrAction->attr('data-test'), 'Custom data-test attribute should be set');
        static::assertSame('Custom label', $attrAction->attr('aria-label'), 'Custom aria-label attribute should be set');
    }

    public function testActionWithIconOnly(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        // Find the icon-only action in the first entity's dropdown
        $firstEntity = $this->actionTestEntities->findOneBy([]);
        static::assertNotNull($firstEntity, 'Test requires at least one entity');

        $row = $crawler->filter(sprintf('tr[data-id="%s"]', $firstEntity->getId()));
        $iconOnlyAction = $row->filter('[data-action-name="iconOnly"]')->first();
        static::assertCount(1, $iconOnlyAction, 'Icon-only action should exist');

        // Verify icon is present (the icon uses the <twig:ea:Icon> component)
        $icon = $iconOnlyAction->filter('.fa-cog, [class*="fa-cog"]');
        static::assertGreaterThanOrEqual(1, $icon->count(), 'Icon should be present in icon-only action');
    }

    // ============================================
    // Custom Template Tests (Global Actions)
    // ============================================

    public function testSetTemplatePathRendersCustomTemplate(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        // Check for element with custom template marker in global actions
        $customTemplateAction = $crawler->filter('.global-actions .custom-template[data-custom-action="true"]');
        static::assertCount(1, $customTemplateAction, 'Action with custom template should render with custom-template class');
    }
}
