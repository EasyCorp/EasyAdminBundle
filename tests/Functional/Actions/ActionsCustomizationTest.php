<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Actions;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Actions\ActionsCustomizationCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\CategoryFactory;

class ActionsCustomizationTest extends AbstractCrudTestCase
{
    protected function getControllerFqcn(): string
    {
        return ActionsCustomizationCrudController::class;
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

    public function testCssClasses(): void
    {
        CategoryFactory::createOne();

        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertSame('dropdown-item action-action1 dropdown-item-variant-default', $crawler->filter('a.dropdown-item:contains("Action1")')->attr('class'));
        static::assertSame('dropdown-item foo dropdown-item-variant-default', $crawler->filter('a.dropdown-item:contains("Action2")')->attr('class'));
        static::assertSame('dropdown-item action-action3 bar dropdown-item-variant-default', $crawler->filter('a.dropdown-item:contains("Action3")')->attr('class'));
        static::assertSame('dropdown-item foo bar dropdown-item-variant-default', $crawler->filter('a.dropdown-item:contains("Action4")')->attr('class'));

        static::assertSame('btn btn-primary btn-solid  action-new', trim($crawler->filter('.global-actions > a[data-action-name="new"]')->attr('class')));
    }

    public function testDynamicLabels(): void
    {
        $entityName = 'Category 1';
        CategoryFactory::createOne(['name' => $entityName]);

        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertSame('Action 5: '.$entityName, $crawler->filter('a.dropdown-item[data-action-name="action5"]')->text());
        static::assertSame('Action 6: '.$entityName, $crawler->filter('a.dropdown-item[data-action-name="action6"]')->text());
        static::assertSame('Action 7: '.$entityName, $crawler->filter('a.dropdown-item[data-action-name="action7"]')->text());
        static::assertSame('Reset', $crawler->filter('a.dropdown-item[data-action-name="action8"]')->text());
    }

    public function testFormAction(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertCount(1, $crawler->filter('.global-actions form.action-action9'));
        static::assertCount(1, $crawler->filter('.global-actions form.action-action9 button'));
        static::assertSame('POST', $crawler->filter('.global-actions form.action-action9')->attr('method'));
    }

    public function testConfirmationWithoutCustomMessage(): void
    {
        CategoryFactory::createOne();

        $crawler = $this->client->request('GET', $this->generateIndexUrl());
        $action = $crawler->filter('a.dropdown-item[data-action-name="action10"]');

        static::assertSame('true', $action->attr('data-action-confirmation'));
        static::assertNull($action->attr('data-action-confirmation-title'));
        static::assertNull($action->attr('data-action-confirmation-message'));
        static::assertNull($action->attr('data-action-confirmation-content'));
    }

    public function testCustomConfirmationMessageReplacesTitle(): void
    {
        CategoryFactory::createOne();

        $crawler = $this->client->request('GET', $this->generateIndexUrl());
        $action = $crawler->filter('a.dropdown-item[data-action-name="action11"]');

        static::assertNull($action->attr('data-action-confirmation-title'));
        static::assertNull($action->attr('data-action-confirmation-content'));
        static::assertSame('Visitors will not see this item anymore.', $action->attr('data-action-confirmation-message'));
    }

    public function testConfirmationDefinedWithClosure(): void
    {
        $category1 = CategoryFactory::createOne(['name' => 'Category 1']);
        $category2 = CategoryFactory::createOne(['name' => 'Category 2']);
        $category3 = CategoryFactory::createOne(['name' => 'Category 3']);

        $crawler = $this->client->request('GET', $this->generateIndexUrl());
        $actionSelector = 'tr[data-id="%s"] a.dropdown-item[data-action-name="action12"]';

        $action = $crawler->filter(sprintf($actionSelector, $category1->getId()));
        static::assertSame('true', $action->attr('data-action-confirmation'));
        static::assertSame('Visitors will not see Category 1 anymore.', $action->attr('data-action-confirmation-message'));

        $action = $crawler->filter(sprintf($actionSelector, $category2->getId()));
        static::assertSame('true', $action->attr('data-action-confirmation'));
        static::assertNull($action->attr('data-action-confirmation-message'));

        $action = $crawler->filter(sprintf($actionSelector, $category3->getId()));
        static::assertCount(1, $action);
        static::assertNull($action->attr('data-action-confirmation'));
        static::assertNull($action->attr('data-action-confirmation-message'));
    }

    public function testConfirmationDefinedWithClosureInGlobalAction(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());
        $action = $crawler->filter('.global-actions [data-action-name="action13"]');

        static::assertCount(1, $action);
        static::assertSame('true', $action->attr('data-action-confirmation'));
        static::assertSame('The closure received 0 arguments.', $action->attr('data-action-confirmation-message'));
        static::assertNull($action->attr('data-action-entity-id'));
    }

    public function testDefaultDeleteConfirmation(): void
    {
        CategoryFactory::createOne();

        $crawler = $this->client->request('GET', $this->generateIndexUrl());
        $action = $crawler->filter('[data-action-name="delete"]')->first();

        static::assertSame('Do you really want to delete this item?', $action->attr('data-action-confirmation-title'));
        static::assertSame('There is no undo for this operation.', $action->attr('data-action-confirmation-content'));
        static::assertNull($action->attr('data-action-confirmation-message'));
    }

    public function testCustomDeleteConfirmationMessageReplacesTitleAndDescription(): void
    {
        $entity = CategoryFactory::createOne();
        $crawler = $this->client->request('GET', $this->generateDetailUrl($entity->getId()));
        $action = $crawler->filter('[data-action-name="delete"]')->first();

        static::assertNull($action->attr('data-action-confirmation-title'));
        static::assertNull($action->attr('data-action-confirmation-content'));
        static::assertSame('All the comments of this item will be deleted too.', $action->attr('data-action-confirmation-message'));
    }

    public function testConfirmationModalTemplate(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertSame('Are you sure you want to %action_name%?', $crawler->filter('#modal-action-confirmation h4#action-confirmation-title')->text());
        static::assertCount(1, $crawler->filter('#modal-action-confirmation p#action-confirmation-message.modal-message.d-none'));
        static::assertCount(1, $crawler->filter('#modal-action-confirmation p#action-confirmation-content.d-none'));
    }
}
