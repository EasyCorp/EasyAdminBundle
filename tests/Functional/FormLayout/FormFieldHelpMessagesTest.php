<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\FormLayout;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\FormFieldHelpSyntheticCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\FormTestEntity;

/**
 * Tests for the ->setHelp() method of fields and how that
 * help message is rendered in forms and detail pages.
 */
class FormFieldHelpMessagesTest extends AbstractCrudTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();
    }

    protected function getControllerFqcn(): string
    {
        return FormFieldHelpSyntheticCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    public function testFieldsHelpMessagesInForms(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        // fields with no help defined
        static::assertSelectorNotExists('#tab-tab-1 .tab-help', 'The Tab 1 does not define a help message.');
        static::assertSelectorNotExists('.form-column.column-1 .form-column-help', 'The Column 1 does not define a help message.');
        static::assertSelectorNotExists('.form-fieldset-title:contains("Fieldset 1") .form-fieldset-help', 'The Fieldset 1 does not define a help message.');
        static::assertSelectorNotExists('.form-group #FormTestEntity_id + .form-help', 'The ID field does not define a help message.');
        static::assertSelectorNotExists('.form-group #FormTestEntity_name + .form-help', 'The name field defines an empty string as a help message, so it does not render an HTML element for that help message.');

        // fields with help defined as simple text strings
        static::assertSelectorTextContains('#tab-tab-2 .tab-help', 'Tab 2 Lorem Ipsum', 'The Tab 2 field defines a text help message.');
        static::assertSelectorTextContains('.form-column.column-2 .form-column-help', 'Column 2 Lorem Ipsum', 'The Column 2 field defines a text help message.');
        static::assertSelectorTextContains('.form-fieldset-title:contains("Fieldset 2") .form-fieldset-help', 'Fieldset 2 Lorem Ipsum', 'The Fieldset 2 field defines a text help message.');
        static::assertSelectorTextContains('.form-group #FormTestEntity_description + .form-help', 'Description Lorem Ipsum', 'The description field defines a text help message.');

        // fields with help defined as text strings with HTML contents
        static::assertSame('<a href="https://example.com">Tab 3</a> <b>Lorem</b> Ipsum', trim($crawler->filter('#tab-tab-3 .tab-help')->html()), 'The Tab 3 field defines a help message with HTML contents, which must be rendered instead of escaped.');
        static::assertSame('<a href="https://example.com">Column 3</a> <b>Lorem</b> Ipsum', trim($crawler->filter('.form-column.column-3 .form-column-help')->html()), 'The Column 3 field defines a help message with HTML contents, which must be rendered instead of escaped.');
        static::assertSame('<a href="https://example.com">Fieldset 3</a> <b>Lorem</b> Ipsum', trim($crawler->filter('.form-fieldset-title:contains("Fieldset 3") .form-fieldset-help')->html()), 'The Fieldset 3 field defines a help message with HTML contents, which must be rendered instead of escaped.');
        static::assertSame('<a href="https://example.com">Email</a> <b>Lorem</b> Ipsum', $crawler->filter('.form-group #FormTestEntity_email + .form-help')->html(), 'The email field defines an help message with HTML contents, which must be rendered instead of escaped.');

        // fields with help defined as Translatable objects using simple text strings
        static::assertSelectorTextContains('#tab-tab-4 .tab-help', 'Tab 4 Lorem Ipsum', 'The Tab 4 field defines a translatable text help message.');
        static::assertSelectorTextContains('.form-column.column-4 .form-column-help', 'Column 4 Lorem Ipsum', 'The Column 4 field defines a translatable text help message.');
        static::assertSelectorTextContains('.form-fieldset-title:contains("Fieldset 4") .form-fieldset-help', 'Fieldset 4 Lorem Ipsum', 'The Fieldset 4 field defines a translatable text help message.');
        static::assertSelectorTextContains('.form-group:contains("Created At") .form-help', 'CreatedAt Lorem Ipsum', 'The createdAt field defines a translatable text help message.');

        // fields with help defined as Translatable objects using text strings with HTML contents
        static::assertSelectorTextContains('#tab-tab-5 .tab-help', 'Tab 5 Lorem Ipsum', 'The Tab 5 field defines a translatable help message with HTML contents, which must be rendered instead of escaped.');
        static::assertSelectorTextContains('.form-column.column-5 .form-column-help', 'Column 5 Lorem Ipsum', 'The Column 5 field defines a translatable help message with HTML contents, which must be rendered instead of escaped..');
        static::assertSelectorTextContains('.form-fieldset-title:contains("Fieldset 5") .form-fieldset-help', 'Fieldset 5 Lorem Ipsum', 'The Fieldset 5 field defines a translatable help message with HTML contents, which must be rendered instead of escaped..');
        static::assertSame('<a href="https://example.com">Priority</a> <b>Lorem</b> Ipsum', $crawler->filter('.form-group:contains("Priority") .form-help')->html(), 'The priority field defines a translatable help message with HTML contents, which must be rendered instead of escaped.');
    }

    public function testFieldsHelpMessagesOnDetailPage(): void
    {
        $entity = new FormTestEntity();
        $entity->setName('Test Entity');
        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        $crawler = $this->client->request('GET', $this->generateDetailUrl($entity->getId()));

        // fields with no help defined
        static::assertSelectorNotExists('#tab-tab-1 .tab-help', 'The Tab 1 does not define a help message.');
        static::assertSelectorNotExists('.form-column.column-1 .form-column-help', 'The Column 1 does not define a help message.');
        static::assertSelectorNotExists('.form-fieldset-title:contains("Fieldset 1") .form-fieldset-help', 'The Fieldset 1 does not define a help message.');
        $idField = $crawler->filterXPath('//div[@class="field-label"]/div[normalize-space(text())="ID"]');
        static::assertGreaterThan(0, $idField->count(), 'ID field should exist');
        static::assertNull($idField->attr('data-bs-title'), 'The ID field does not define a help message (no tooltip attributes).');
        $nameField = $crawler->filterXPath('//div[@class="field-label"]/div[normalize-space(text())="Name"]');
        static::assertGreaterThan(0, $nameField->count(), 'Name field should exist');
        static::assertNull($nameField->attr('data-bs-title'), 'The name field defines an empty string as a help message, so no tooltip is rendered.');

        // fields with help defined as simple text strings
        static::assertSelectorTextContains('#tab-tab-2 .tab-help', 'Tab 2 Lorem Ipsum', 'The Tab 2 field defines a text help message.');
        static::assertSelectorTextContains('.form-column.column-2 .form-column-help', 'Column 2 Lorem Ipsum', 'The Column 2 field defines a text help message.');
        static::assertSelectorTextContains('.form-fieldset-title:contains("Fieldset 2") .form-fieldset-help', 'Fieldset 2 Lorem Ipsum', 'The Fieldset 2 field defines a text help message.');
        $descriptionLabel = $crawler->filterXPath('//div[@class="field-label"]/div[normalize-space(text())="Description"]');
        static::assertSame(1, $descriptionLabel->count(), 'Description field should exist');
        static::assertStringContainsString('Description Lorem Ipsum', $descriptionLabel->attr('data-bs-title'), 'The description field defines a text help message shown in a tooltip on detail pages.');

        // fields with help defined as text strings with HTML contents
        static::assertSame('<a href="https://example.com">Tab 3</a> <b>Lorem</b> Ipsum', trim($crawler->filter('#tab-tab-3 .tab-help')->html()), 'The Tab 3 field defines a help message with HTML contents, which must be rendered instead of escaped.');
        static::assertSame('<a href="https://example.com">Column 3</a> <b>Lorem</b> Ipsum', trim($crawler->filter('.form-column.column-3 .form-column-help')->html()), 'The Column 3 field defines a help message with HTML contents, which must be rendered instead of escaped.');
        static::assertSame('<a href="https://example.com">Fieldset 3</a> <b>Lorem</b> Ipsum', trim($crawler->filter('.form-fieldset-title:contains("Fieldset 3") .form-fieldset-help')->html()), 'The Fieldset 3 field defines a help message with HTML contents, which must be rendered instead of escaped.');
        $emailLabel = $crawler->filterXPath('//div[@class="field-label"]/div[normalize-space(text())="Email"]');
        static::assertStringContainsString('<a href="https://example.com">Email</a> <b>Lorem</b> Ipsum', $emailLabel->attr('data-bs-title'), 'The email field defines a help message with HTML contents shown in a tooltip (HTML is stored in the data-bs-title attribute and will be rendered by Bootstrap).');

        // fields with help defined as Translatable objects using simple text strings
        static::assertSelectorTextContains('#tab-tab-4 .tab-help', 'Tab 4 Lorem Ipsum', 'The Tab 4 field defines a translatable text help message.');
        static::assertSelectorTextContains('.form-column.column-4 .form-column-help', 'Column 4 Lorem Ipsum', 'The Column 4 field defines a translatable text help message.');
        static::assertSelectorTextContains('.form-fieldset-title:contains("Fieldset 4") .form-fieldset-help', 'Fieldset 4 Lorem Ipsum', 'The Fieldset 4 field defines a translatable text help message.');
        $createdAtLabel = $crawler->filterXPath('//div[@class="field-label"]/div[normalize-space(text())="Created At"]');
        static::assertStringContainsString('CreatedAt Lorem Ipsum', $createdAtLabel->attr('data-bs-title'), 'The createdAt field defines a translatable text help message shown in a tooltip on detail pages.');

        // fields with help defined as Translatable objects using text strings with HTML contents
        static::assertSelectorTextContains('#tab-tab-5 .tab-help', 'Tab 5 Lorem Ipsum', 'The Tab 5 field defines a translatable help message with HTML contents, which must be rendered instead of escaped.');
        static::assertSelectorTextContains('.form-column.column-5 .form-column-help', 'Column 5 Lorem Ipsum', 'The Column 5 field defines a translatable help message with HTML contents, which must be rendered instead of escaped..');
        static::assertSelectorTextContains('.form-fieldset-title:contains("Fieldset 5") .form-fieldset-help', 'Fieldset 5 Lorem Ipsum', 'The Fieldset 5 field defines a translatable help message with HTML contents, which must be rendered instead of escaped..');
        $priorityLabel = $crawler->filterXPath('//div[@class="field-label"]/div[normalize-space(text())="Priority"]');
        static::assertStringContainsString('<a href="https://example.com">Priority</a> <b>Lorem</b> Ipsum', $priorityLabel->attr('data-bs-title'), 'The priority field defines a translatable help message with HTML contents shown in a tooltip (HTML is stored in the data-bs-title attribute and will be rendered by Bootstrap).');
    }
}
