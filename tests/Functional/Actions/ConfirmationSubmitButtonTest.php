<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Actions;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Actions\ConfirmationSubmitButtonCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\CategoryFactory;

class ConfirmationSubmitButtonTest extends AbstractCrudTestCase
{
    protected function getControllerFqcn(): string
    {
        return ConfirmationSubmitButtonCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    public function testConfirmationButtonTargetsTheEditForm(): void
    {
        $category = CategoryFactory::createOne(['name' => 'Before edit', 'slug' => 'before-edit']);

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($category->getId()));
        static::assertResponseIsSuccessful();

        $button = $crawler->filter('button[data-action-name="confirmSave"][data-action-confirmation="true"]');
        static::assertCount(1, $button);
        static::assertSame('submit', $button->attr('type'));
        static::assertSame('edit-Category-form', $button->attr('form'));
        static::assertCount(1, $crawler->filter('form#edit-Category-form'));
        static::assertCount(1, $crawler->filter('#modal-action-confirmation-button'));
    }

    public function testConfirmationButtonTargetsTheNewForm(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());
        static::assertResponseIsSuccessful();

        $button = $crawler->filter('button[data-action-name="confirmCreate"][data-action-confirmation="true"]');
        static::assertCount(1, $button);
        static::assertSame('submit', $button->attr('type'));
        static::assertSame('new-Category-form', $button->attr('form'));
        static::assertCount(1, $crawler->filter('form#new-Category-form'));
        static::assertCount(1, $crawler->filter('#modal-action-confirmation-button'));
    }
}
