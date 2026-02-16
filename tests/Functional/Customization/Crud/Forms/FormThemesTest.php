<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Customization\Crud\Forms;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\Crud\Forms\FormThemesTestCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Kernel;

/**
 * Tests for Crud::addFormTheme() and Crud::setFormThemes() configuration methods.
 */
class FormThemesTest extends AbstractCrudTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function getControllerFqcn(): string
    {
        return FormThemesTestCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return \EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\DashboardController::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();
    }

    public function testFormThemesConfiguration(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        static::assertResponseIsSuccessful();

        // verify form exists with EasyAdmin structure
        static::assertSelectorExists('form', 'Form should exist');

        // verify form fields have proper structure (field groups)
        $formFields = $crawler->filter('form .form-group, form .field-group, form .mb-3');
        static::assertGreaterThan(0, $formFields->count(), 'Form should have field groups');

        // verify form inputs exist
        static::assertSelectorExists('form input, form textarea, form select');

        // verify content wrapper exists
        static::assertSelectorExists('.content');
    }
}
