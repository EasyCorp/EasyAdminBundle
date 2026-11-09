<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\UiKit;

use EasyCorp\Bundle\EasyAdminBundle\DataCollector\EasyAdminDataCollector;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\HiddenField;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class FormFieldsTest extends WebTestCase
{
    private const URL = '/admin/_ea_ui_kit/form_fields';

    protected function tearDown(): void
    {
        parent::tearDown();
        self::ensureKernelShutdown();
    }

    public function testIndexLinksToTheFormFields(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/admin/_ea_ui_kit');

        $this->assertSame(self::URL, $crawler->filter('#main ul a:contains("Form fields")')->attr('href'));
    }

    public function testFormRendersEveryNativeField(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::URL);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('body.ea.ea-ui-kit #main-menu');
        $this->assertSelectorTextContains('h1', 'Form fields');

        $labels = $crawler->filter('form.ea-new-form label, form.ea-new-form legend')->each(static fn ($label) => trim($label->text()));
        // these fields can't be rendered without a Doctrine entity (AssociationField), don't
        // render a label (HiddenField) or aren't a form field (Field, FormField)
        $excludedFields = [AssociationField::class, Field::class, FormField::class, HiddenField::class];
        foreach (glob(__DIR__.'/../../../src/Field/*Field.php') as $fieldPath) {
            $fieldName = basename($fieldPath, '.php');
            if (\in_array('EasyCorp\\Bundle\\EasyAdminBundle\\Field\\'.$fieldName, $excludedFields, true)) {
                continue;
            }

            $this->assertContains($fieldName, $labels, sprintf('The form field reference of the UI Kit doesn\'t include the "%s" field.', $fieldName));
        }

        $this->assertSelectorExists('form.ea-new-form input[type="hidden"][name="FormFieldReference[hidden]"]');
        $this->assertSame('Lorem ipsum dolor sit amet', $crawler->filter('#FormFieldReference_text')->attr('value'));
    }

    public function testFormLoadsTheAssetsOfTheFields(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::URL);

        $scripts = implode(' ', $crawler->filter('script[src]')->each(static fn ($script) => $script->attr('src')));
        $stylesheets = implode(' ', $crawler->filter('link[rel="stylesheet"]')->each(static fn ($link) => $link->attr('href')));

        foreach (['form', 'field-slug', 'field-text-editor', 'field-code-editor', 'field-collection', 'field-file-upload'] as $script) {
            $this->assertMatchesRegularExpression(sprintf('#/bundles/easyadmin/%s\.[a-f0-9]+\.js#', $script), $scripts);
        }
        foreach (['field-text-editor', 'field-code-editor'] as $stylesheet) {
            $this->assertMatchesRegularExpression(sprintf('#/bundles/easyadmin/%s\.[a-f0-9]+\.css#', $stylesheet), $stylesheets);
        }
    }

    public function testStateLinks(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::URL.'?state=errors');

        $links = $crawler->filter('nav[aria-label="Field state"] a');
        $this->assertSame(['Default', 'Help', 'Errors', 'Disabled'], $links->each(static fn ($link) => $link->text()));
        $this->assertSame(self::URL, $links->eq(0)->attr('href'));
        $this->assertSame(self::URL.'?state=errors', $links->eq(2)->attr('href'));
        $this->assertSame('page', $links->eq(2)->attr('aria-current'));
        $this->assertNull($links->eq(0)->attr('aria-current'));
    }

    public function testDefaultStateHasNoHelpErrorsOrDisabledFields(): void
    {
        $client = static::createClient();
        $client->request('GET', self::URL);

        $this->assertSelectorNotExists('form.ea-new-form .form-help');
        $this->assertSelectorNotExists('form.ea-new-form .has-error');
        $this->assertSelectorNotExists('form.ea-new-form [disabled]');
    }

    public function testHelpState(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::URL.'?state=help');

        $this->assertSame('Lorem ipsum dolor sit amet, consectetur adipiscing elit.', trim($crawler->filter('.field-text .form-help')->text()));
        $this->assertSelectorExists('.field-code_editor .form-help');
    }

    public function testDisabledState(): void
    {
        $client = static::createClient();
        $client->request('GET', self::URL.'?state=disabled');

        $this->assertSelectorExists('#FormFieldReference_text[disabled]');
        $this->assertSelectorExists('#FormFieldReference_boolean[disabled]');
        $this->assertSelectorExists('#FormFieldReference_choice[disabled]');
    }

    public function testErrorsState(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', self::URL.'?state=errors');

        $this->assertResponseIsSuccessful();
        $this->assertSame('Lorem ipsum dolor sit amet.', trim($crawler->filter('.field-text.has-error .invalid-feedback')->text()));
        $this->assertSelectorExists('.field-boolean.has-error');
        $this->assertSelectorExists('.field-date.has-error');
        // the form is submitted with its own data, so the values don't change
        $this->assertSame('Lorem ipsum dolor sit amet', $crawler->filter('#FormFieldReference_text')->attr('value'));
        $this->assertSame(['1'], $crawler->filter('input[name="FormFieldReference[choiceExpanded]"]:checked')->extract(['value']));
        $this->assertSame(['0', '2'], $crawler->filter('input[name="FormFieldReference[choiceExpandedMultiple][]"]:checked')->extract(['value']));
        // errors are only added to the fields that display them, not to the top of the form
        $this->assertSelectorNotExists('.global-invalid-feedback');
    }

    public function testProfilerCollectsTheFormPage(): void
    {
        $client = static::createClient();
        $client->enableProfiler();
        $client->request('GET', self::URL);

        $this->assertResponseIsSuccessful();
        /** @var EasyAdminDataCollector $collector */
        $collector = $client->getProfile()->getCollector('easyadmin');
        $crud = $collector->getData()['crud'];
        $this->assertNull($crud['controller_fqcn']);
        $this->assertSame('new', $crud['page']);
    }

    public function testUnknownStateIsNotFound(): void
    {
        $client = static::createClient();
        $client->request('GET', self::URL.'?state=foo');

        $this->assertResponseStatusCodeSame(404);
    }
}
