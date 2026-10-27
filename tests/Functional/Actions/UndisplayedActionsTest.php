<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Actions;

use EasyCorp\Bundle\EasyAdminBundle\Exception\ForbiddenActionException;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Actions\UndisplayedActionsCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Actions\UndisplayedActionsWithPermissionCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\CategoryFactory;

class UndisplayedActionsTest extends AbstractCrudTestCase
{
    protected function getControllerFqcn(): string
    {
        return UndisplayedActionsCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    public function testUndisplayedActionIsDisabled(): void
    {
        $this->expectException(ForbiddenActionException::class);
        $this->client->catchExceptions(false);

        $this->client->request('GET', $this->generateNewFormUrl());
    }

    public function testActionDisplayedOnAnotherPageIsEnabled(): void
    {
        $category = CategoryFactory::createOne();

        $crawler = $this->client->request('GET', $this->generateIndexUrl());
        static::assertCount(0, $crawler->filter(sprintf('tr[data-id="%s"] [data-action-name="edit"]', $category->getId())));

        $this->client->request('GET', $this->generateEditFormUrl($category->getId()));
        static::assertResponseIsSuccessful();
    }

    public function testUndisplayedActionWithPermissionIsEnabled(): void
    {
        $this->client->request('GET', $this->generateNewFormUrl(null, UndisplayedActionsWithPermissionCrudController::class));

        static::assertResponseIsSuccessful();
    }
}
