<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\FormLayout;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\FormFieldsetHtmlAttributesCrudController;

/**
 * Regression test for #6285 / PR #7593: HTML attributes set via
 * setHtmlAttribute() or setFormTypeOptions(['attr' => ...]) on a fieldset
 * must reach the rendered .form-fieldset wrapper div.
 */
class FormFieldsetHtmlAttributesTest extends AbstractCrudTestCase
{
    protected function getControllerFqcn(): string
    {
        return FormFieldsetHtmlAttributesCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    public function testSetHtmlAttributeIsRenderedOnFieldsetWrapper(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $fieldset = $crawler->filter('.form-fieldset[data-foo="bar"]');

        static::assertCount(1, $fieldset, 'Custom data-* attribute set via setHtmlAttribute() should be rendered on the .form-fieldset wrapper');
        static::assertStringContainsString('custom-fieldset-class', $fieldset->attr('class'), 'Custom class set via setHtmlAttribute() should be merged into the wrapper class list');
        static::assertStringContainsString('form-fieldset', $fieldset->attr('class'), 'The default form-fieldset class must be preserved alongside the custom class');
    }

    public function testSetFormTypeOptionsAttrIsRenderedOnFieldsetWrapper(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        static::assertCount(
            1,
            $crawler->filter('.form-fieldset[data-baz="qux"]'),
            'Custom attribute set via setFormTypeOptions([\'attr\' => ...]) should be rendered on the .form-fieldset wrapper'
        );
    }
}
