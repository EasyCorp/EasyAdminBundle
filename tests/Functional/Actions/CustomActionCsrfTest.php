<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Actions;

use Doctrine\ORM\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\ActionTestEntityCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\ActionTestEntity;

/**
 * Anchors rule A7 of skills/easyadmin/SKILL.md: a custom action configured with
 * renderAsForm() gets a bare POST <form> with no CSRF token of any kind, while
 * the built-in delete action does get one (through the shared confirmation form).
 *
 * If this behavior changes, update that skill rule too.
 */
class CustomActionCsrfTest extends AbstractCrudTestCase
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

        $this->actionTestEntities = $this->entityManager->getRepository(ActionTestEntity::class);
    }

    public function testGlobalActionRenderedAsFormHasNoCsrfToken(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        $actionForm = $crawler->filter('.global-actions form.action-formAction');
        static::assertCount(1, $actionForm);
        static::assertSame('POST', mb_strtoupper((string) $actionForm->attr('method')));

        static::assertCount(0, $actionForm->filter('input[name="_token"]'));
        static::assertCount(0, $actionForm->filter('input[name="token"]'));
        static::assertCount(0, $actionForm->filter('input[type="hidden"]'));
        static::assertCount(0, $actionForm->filter('input'));
    }

    public function testEntityActionRenderedAsFormHasNoCsrfToken(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        $entity = $this->actionTestEntities->findOneBy([]);
        static::assertInstanceOf(ActionTestEntity::class, $entity);

        $actionLink = $crawler->filter(sprintf('tr[data-id="%s"] [data-action-name="entityFormAction"]', $entity->getId()));
        static::assertCount(1, $actionLink);

        $actionForm = $actionLink->closest('li')->filter('form');
        static::assertCount(1, $actionForm);
        static::assertSame('POST', mb_strtoupper((string) $actionForm->attr('method')));

        static::assertCount(0, $actionForm->filter('input[name="_token"]'));
        static::assertCount(0, $actionForm->filter('input[name="token"]'));
        static::assertCount(0, $actionForm->filter('input'));
    }

    public function testDeleteActionIsSubmittedThroughAFormThatCarriesACsrfToken(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        $deletableEntity = $this->actionTestEntities->findOneBy(['isDeletable' => true]);
        static::assertInstanceOf(ActionTestEntity::class, $deletableEntity);

        $deleteAction = $crawler->filter(sprintf('tr[data-id="%s"] [data-action-name="delete"]', $deletableEntity->getId()));
        static::assertCount(1, $deleteAction);
        static::assertNotNull($deleteAction->attr('formaction'));
        static::assertSame('true', $deleteAction->attr('data-action-confirmation'));

        $confirmationForm = $crawler->filter('form#action-confirmation-form');
        static::assertCount(1, $confirmationForm);
        static::assertSame('post', mb_strtolower((string) $confirmationForm->attr('method')));

        $csrfTokenInput = $confirmationForm->filter('input[name="token"]');
        static::assertCount(1, $csrfTokenInput);
        static::assertNotSame('', (string) $csrfTokenInput->attr('value'));
    }
}
