<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Customization\Crud\Forms;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\Crud\Forms\FormOptionsTestCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Kernel;

/**
 * Tests for Crud::setFormOptions() configuration method.
 */
class FormOptionsTest extends AbstractCrudTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function getControllerFqcn(): string
    {
        return FormOptionsTestCrudController::class;
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

    public function testFormOptionsConfiguration(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        static::assertResponseIsSuccessful();

        // verify form exists
        static::assertSelectorExists('form', 'Form should exist');

        // verify form fields are rendered
        static::assertSelectorExists('form input, form textarea, form select');

        // verify content wrapper exists
        static::assertSelectorExists('.content');

        // verify form structure (field containers)
        $formFields = $crawler->filter('form .form-group, form .field-group, form .mb-3');
        static::assertGreaterThan(0, $formFields->count(), 'Form should have field groups');
    }
}
