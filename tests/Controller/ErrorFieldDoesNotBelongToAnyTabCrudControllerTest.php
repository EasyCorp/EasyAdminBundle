<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller\ErrorDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller\ErrorFieldDoesNotBelongToAnyTabCrudController;

class ErrorFieldDoesNotBelongToAnyTabCrudControllerTest extends AbstractCrudTestCase
{
    protected function getControllerFqcn(): string
    {
        return ErrorFieldDoesNotBelongToAnyTabCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return ErrorDashboardController::class;
    }

    public function testErrorInFormPages()
    {
        $this->client->catchExceptions(false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The "new" page of "EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller\ErrorFieldDoesNotBelongToAnyTabCrudController" uses tabs to display its fields, but the following fields don\'t belong to any tab: name, slug. Use "FormField::addTab(\'...\')" to add a tab before those fields.');

        $this->client->request('GET', $this->generateNewFormUrl());
    }
}
