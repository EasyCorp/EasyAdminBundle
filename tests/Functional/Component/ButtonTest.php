<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Component;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\AbstractFieldFunctionalTest;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Rendering tests for the <twig:ea:Button> component.
 */
class ButtonTest extends AbstractFieldFunctionalTest
{
    use ExpectDeprecationTrait;

    private function renderButton(string $template): string
    {
        return trim(static::getContainer()->get('twig')->createTemplate($template)->render());
    }

    public function testDefaultButton(): void
    {
        $html = $this->renderButton('<twig:ea:Button>Save</twig:ea:Button>');

        self::assertStringContainsString('<button class="btn btn-secondary" type="submit">', $html);
        self::assertStringContainsString('<span class="btn-label">Save</span>', $html);
        self::assertStringContainsString('</button>', $html);
    }

    public function testVariantAndCustomClassAreMerged(): void
    {
        $html = $this->renderButton('<twig:ea:Button variant="danger" class="my-button">x</twig:ea:Button>');

        self::assertStringContainsString('class="btn btn-danger my-button"', $html);
    }

    public function testLinkWithHref(): void
    {
        $html = $this->renderButton('<twig:ea:Button htmlElement="a" href="https://example.com">Go</twig:ea:Button>');

        self::assertStringContainsString('<a class="btn btn-secondary" role="button" href="https://example.com">', $html);
        self::assertStringContainsString('</a>', $html);
    }

    public function testLinkWithoutHrefOmitsTheAttribute(): void
    {
        $html = $this->renderButton('<twig:ea:Button htmlElement="a">Go</twig:ea:Button>');

        self::assertStringNotContainsString('href', $html);
        self::assertStringContainsString('role="button"', $html);
    }

    public function testInactiveLinkHasNoHref(): void
    {
        $html = $this->renderButton('<twig:ea:Button htmlElement="a" href="https://example.com" inactive>Go</twig:ea:Button>');

        self::assertStringNotContainsString('href', $html);
        self::assertStringContainsString('aria-disabled="true"', $html);
        self::assertStringContainsString('tabindex="-1"', $html);
        self::assertStringContainsString('class="btn btn-secondary disabled"', $html);
    }

    public function testInactiveButtonIsDisabled(): void
    {
        $html = $this->renderButton('<twig:ea:Button inactive>x</twig:ea:Button>');

        self::assertStringContainsString('disabled', $html);
        self::assertStringContainsString('class="btn btn-secondary disabled"', $html);
    }

    public function testFormButton(): void
    {
        $html = $this->renderButton('<twig:ea:Button htmlElement="form" action="/delete" class="action-delete" id="delete-button" data-foo="bar">Delete</twig:ea:Button>');

        // the wrapper <form> only keeps the CSS class; all the other attributes
        // are rendered once, in the inner <button>
        self::assertStringContainsString('<form action="/delete" method="POST" class="action-delete">', $html);
        self::assertSame(1, substr_count($html, 'id="delete-button"'));
        self::assertSame(1, substr_count($html, 'data-foo="bar"'));
        self::assertStringContainsString('<button class="btn btn-secondary action-delete"', $html);
        self::assertStringNotContainsString('_method', $html);
    }

    public function testFormButtonWithoutRequestDoesNotIncludeCsrfToken(): void
    {
        $html = $this->renderButton('<twig:ea:Button htmlElement="form" action="/delete">Delete</twig:ea:Button>');

        self::assertStringNotContainsString('name="token"', $html);
    }

    public function testFormButtonIncludesCsrfTokenWhenSessionIsAvailable(): void
    {
        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));
        $requestStack = static::getContainer()->get('request_stack');
        $requestStack->push($request);

        try {
            $html = $this->renderButton('<twig:ea:Button htmlElement="form" action="/delete">Delete</twig:ea:Button>');
        } finally {
            $requestStack->pop();
        }

        self::assertMatchesRegularExpression('/<input type="hidden" name="token" value="[^"]+">/', $html);
    }

    public function testFormButtonWithMethodOverride(): void
    {
        $html = $this->renderButton('<twig:ea:Button htmlElement="form" action="/delete" method="delete">Delete</twig:ea:Button>');

        self::assertStringContainsString('method="POST"', $html);
        self::assertStringContainsString('<input type="hidden" name="_method" value="DELETE">', $html);
    }

    public function testFormButtonWithNameAndValue(): void
    {
        $html = $this->renderButton('<twig:ea:Button htmlElement="form" action="/batch" name="batch" value="delete">x</twig:ea:Button>');

        self::assertStringContainsString('name="batch"', $html);
        self::assertStringContainsString('value="delete"', $html);
    }

    public function testSpreadAttributesLandOnTheRootElement(): void
    {
        $html = $this->renderButton('<twig:ea:Button {{ ...{\'data-foo\': \'bar\'} }}>x</twig:ea:Button>');

        self::assertStringContainsString('data-foo="bar"', $html);
    }

    /**
     * @group legacy
     */
    public function testDeprecatedHtmlAttributesPropStillRenders(): void
    {
        $this->expectDeprecation('Since easycorp/easyadmin-bundle 5.2.0: The "htmlAttributes" prop of the <twig:ea:Button> component is deprecated, pass the attributes directly on the component (or use the "{{ ... }}" spread syntax for dynamic maps) instead.');

        $html = $this->renderButton('<twig:ea:Button htmlAttributes="{{ {\'data-foo\': \'bar\'} }}">x</twig:ea:Button>');

        self::assertStringContainsString('data-foo="bar"', $html);
        self::assertStringNotContainsString('htmlattributes', $html);
    }

    public function testLabelNestedAttributesAreMergedIntoTheLabel(): void
    {
        $html = $this->renderButton('<twig:ea:Button label:class="visually-hidden">x</twig:ea:Button>');

        self::assertStringContainsString('<span class="btn-label visually-hidden">x</span>', $html);
        self::assertStringNotContainsString('label:class', $html);
    }

    public function testLeadingIconIsRenderedBeforeLabel(): void
    {
        $html = $this->renderButton('<twig:ea:Button icon="internal:check">Label</twig:ea:Button>');

        self::assertStringContainsString('btn-icon', $html);
        self::assertStringNotContainsString('btn-icon-trailing', $html);
        self::assertLessThan(strpos($html, 'Label'), strpos($html, 'btn-icon'));
    }

    public function testTrailingIconIsRenderedAfterLabel(): void
    {
        $html = $this->renderButton('<twig:ea:Button icon="internal:check" withTrailingIcon>Label</twig:ea:Button>');

        self::assertStringContainsString('btn-icon-trailing', $html);
        self::assertGreaterThan(strpos($html, 'Label'), strpos($html, 'btn-icon-trailing'));
    }

    public function testNoLabelSpanWithoutContent(): void
    {
        $html = $this->renderButton('<twig:ea:Button icon="internal:check" aria-label="Close" />');

        self::assertStringNotContainsString('btn-label', $html);
        self::assertStringContainsString('aria-label="Close"', $html);
    }

    public function testAttributeValuesAreEscaped(): void
    {
        $html = $this->renderButton('<twig:ea:Button data-title="{{ \'a\' ~ \'"\' ~ \'b\' }}">x</twig:ea:Button>');

        self::assertStringNotContainsString('a"b', $html);
        self::assertStringContainsString('a&quot;b', $html);
    }

    public function testEnumValuesArePassedAsSingleExpressionAttributes(): void
    {
        $html = $this->renderButton(<<<'TWIG'
            {% set element = enum('EasyCorp\\Bundle\\EasyAdminBundle\\Twig\\Component\\Option\\ButtonElement').A %}
            {% set variant = enum('EasyCorp\\Bundle\\EasyAdminBundle\\Twig\\Component\\Option\\ButtonVariant').Danger %}
            <twig:ea:Button htmlElement="{{ element }}" variant="{{ variant }}" href="/foo">x</twig:ea:Button>
            TWIG);

        self::assertStringContainsString('<a class="btn btn-danger" role="button" href="/foo">', $html);
    }
}
