<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Customization\Crud\Forms;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\Crud\Forms\FormThemeExtendingCrudTemplateTestCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Kernel;

/**
 * Symfony resolves the parent templates of form themes using only the Twig globals,
 * so this checks that the EasyAdmin CRUD templates can be extended from a form theme.
 */
class FormThemeExtendingCrudTemplateTest extends AbstractCrudTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function getControllerFqcn(): string
    {
        return FormThemeExtendingCrudTemplateTestCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    public function testFormThemeCanExtendCrudTemplate(): void
    {
        $this->client->request('GET', $this->generateNewFormUrl());

        static::assertResponseIsSuccessful();
        static::assertSelectorExists('form .custom-form-theme-row');
    }
}
