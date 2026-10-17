<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Actions;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\ActionsCustomizationCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\ActionTestEntityFactory;

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
        ActionTestEntityFactory::createOne();

        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertSame('dropdown-item action-action1 dropdown-item-variant-default', $crawler->filter('a.dropdown-item:contains("Action1")')->attr('class'));
        static::assertSame('dropdown-item foo dropdown-item-variant-default', $crawler->filter('a.dropdown-item:contains("Action2")')->attr('class'));
        static::assertSame('dropdown-item action-action3 bar dropdown-item-variant-default', $crawler->filter('a.dropdown-item:contains("Action3")')->attr('class'));
        static::assertSame('dropdown-item foo bar dropdown-item-variant-default', $crawler->filter('a.dropdown-item:contains("Action4")')->attr('class'));

        static::assertSame('btn btn-primary  action-new', trim($crawler->filter('.global-actions > a')->first()->attr('class')));
    }

    public function testDynamicLabels(): void
    {
        $entityName = 'Summer sale banner';
        ActionTestEntityFactory::createOne(['name' => $entityName]);

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
        ActionTestEntityFactory::createOne();

        $crawler = $this->client->request('GET', $this->generateIndexUrl());
        $action = $crawler->filter('a.dropdown-item[data-action-name="action10"]');

        static::assertSame('true', $action->attr('data-action-confirmation'));
        static::assertNull($action->attr('data-action-confirmation-title'));
        static::assertNull($action->attr('data-action-confirmation-message'));
        static::assertNull($action->attr('data-action-confirmation-content'));
    }

    public function testCustomConfirmationMessageReplacesTitle(): void
    {
        ActionTestEntityFactory::createOne();

        $crawler = $this->client->request('GET', $this->generateIndexUrl());
        $action = $crawler->filter('a.dropdown-item[data-action-name="action11"]');

        static::assertNull($action->attr('data-action-confirmation-title'));
        static::assertNull($action->attr('data-action-confirmation-content'));
        static::assertSame('Visitors will not see this item anymore.', $action->attr('data-action-confirmation-message'));
    }

    public function testDefaultDeleteConfirmation(): void
    {
        ActionTestEntityFactory::createOne();

        $crawler = $this->client->request('GET', $this->generateIndexUrl());
        $action = $crawler->filter('[data-action-name="delete"]')->first();

        static::assertSame('Do you really want to delete this item?', $action->attr('data-action-confirmation-title'));
        static::assertSame('There is no undo for this operation.', $action->attr('data-action-confirmation-content'));
        static::assertNull($action->attr('data-action-confirmation-message'));
    }

    public function testCustomDeleteConfirmationMessageReplacesTitleAndDescription(): void
    {
        $entity = ActionTestEntityFactory::createOne();
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
