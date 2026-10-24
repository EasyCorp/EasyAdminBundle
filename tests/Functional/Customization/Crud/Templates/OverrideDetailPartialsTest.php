<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Customization\Crud\Templates;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\Crud\Templates\OverrideDetailPartialsTestCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Factory\CategoryFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Kernel;

/**
 * Tests that the individual templates that compose the detail page ('crud/detail/*')
 * can be overridden both via Crud::overrideTemplate() and via the standard Symfony
 * mechanism of the 'templates/bundles/EasyAdminBundle/' directory.
 */
class OverrideDetailPartialsTest extends AbstractCrudTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function getControllerFqcn(): string
    {
        return OverrideDetailPartialsTestCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return \EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\DashboardController::class;
    }

    public function testDetailPartialOverriddenViaOverrideTemplate(): void
    {
        $entity = CategoryFactory::createOne(['name' => 'Detail page entity']);

        $crawler = $this->client->request('GET', $this->generateDetailUrl($entity->getId()));

        static::assertResponseIsSuccessful();

        // all field groups must come from the template configured with overrideTemplate()
        static::assertSelectorExists('[data-test="override-template-field-group"]');
        static::assertSelectorNotExists('.field-group:not(.custom-field-group)');

        // the entity contents are still rendered inside the custom template
        $fieldValues = $crawler->filter('.custom-field-group .field-value')->each(static fn ($node) => trim($node->text()));
        static::assertContains('Detail page entity', $fieldValues);
    }

    public function testDetailPartialOverriddenViaBundleTemplatesDir(): void
    {
        $entity = CategoryFactory::createOne();

        $this->client->request('GET', $this->generateDetailUrl($entity->getId()));

        static::assertResponseIsSuccessful();

        // the fieldset opening tags come from templates/bundles/EasyAdminBundle/crud/detail/fieldset_open.html.twig
        static::assertSelectorExists('[data-test="bundle-override-fieldset-open"]');

        // the open/close pairing is intact: fields render inside the overridden fieldset
        static::assertSelectorExists('.custom-fieldset .row .field-group');
    }
}
