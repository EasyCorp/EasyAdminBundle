<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller\BlogPostCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller\DashboardController;

class CrudControllerSpecificEntityPermissionForNew extends AbstractCrudTestCase
{
    public function testNewActionIsForbiddenForNonLoggedUsers(): void
    {
        $this->client->request('GET', $this->generateNewFormUrl());

        self::assertResponseStatusCodeSame(403);
    }

    public function testNewActionIsForbiddenForNonAdminUsers(): void
    {
        $this->client->setServerParameters(['PHP_AUTH_USER' => 'user', 'PHP_AUTH_PW' => '1234']);
        $this->client->request('GET', $this->generateNewFormUrl());

        self::assertResponseStatusCodeSame(403);
    }

    public function testNewActionNotVisibleForNonAdminUsers(): void
    {
        $this->client->setServerParameters(['PHP_AUTH_USER' => 'user', 'PHP_AUTH_PW' => '1234']);
        $this->client->request('GET', $this->generateIndexUrl());

        self::assertGlobalActionNotExists(Action::NEW);
    }

    public function testNewActionVisibleForAdminUsers(): void
    {
        $this->client->setServerParameters(['PHP_AUTH_USER' => 'admin', 'PHP_AUTH_PW' => '1234']);
        $this->client->request('GET', $this->generateIndexUrl());

        self::assertGlobalActionExists(Action::NEW);
    }

    public function testNewActionIsAllowedForAdminUsers(): void
    {
        $this->client->setServerParameters(['PHP_AUTH_USER' => 'admin', 'PHP_AUTH_PW' => '1234']);
        $this->client->request('GET', $this->generateNewFormUrl());

        self::assertResponseIsSuccessful();
    }

    public function testNewActionOnEntityWithoutPermissionIsNotForbiddenForNonAdminUsers(): void
    {
        $this->client->setServerParameters(['PHP_AUTH_USER' => 'user', 'PHP_AUTH_PW' => '1234']);
        $this->client->request('GET', $this->generateNewFormUrl(controllerFqcn: BlogPostCrudController::class));

        self::assertResponseIsSuccessful();
    }

    protected function getControllerFqcn(): string
    {
        return CategoryCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }
}
