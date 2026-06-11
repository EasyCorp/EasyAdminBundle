<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Field;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\UrlConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Url;

class UrlFieldTest extends AbstractFieldTest
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testDefaultFieldOptions(): void
    {
        $this->initializeConfigurator();

        $field = UrlField::new('foo');
        $fieldDto = $this->configure($field, actionName: Action::EDIT);

        self::assertSame('url', $fieldDto->getFormTypeOption('attr.inputmode'));
        self::assertNull($fieldDto->getCustomOption(UrlField::OPTION_DEFAULT_PROTOCOL));
    }

    /**
     * @testWith [""]
     *           ["http"]
     *           ["https"]
     *           ["ftp"]
     */
    public function testDefaultProtocolOption(string $defaultProtocol): void
    {
        $this->initializeConfigurator();

        $field = UrlField::new('foo');
        $field->setDefaultProtocol($defaultProtocol);
        $fieldDto = $this->configure($field, actionName: Action::EDIT);

        self::assertSame($defaultProtocol, $fieldDto->getCustomOption(UrlField::OPTION_DEFAULT_PROTOCOL));
        self::assertSame($defaultProtocol, $fieldDto->getFormTypeOption('default_protocol'));
    }

    /**
     * @testWith ["http://example.com", "example.com"]
     *           ["https://example.com", "example.com"]
     *           ["http://www.example.com", "example.com"]
     *           ["https://www.example.com", "example.com"]
     *           ["https://01234567890123456789012345678901234567890123456789.com", "0123456789012345678901234567890…"]
     */
    public function testFormattedValuesOnIndexAction(string $url, string $expectedRenderedUrl): void
    {
        $this->initializeConfigurator();

        $field = UrlField::new('foo')->setValue($url);
        $fieldDto = $this->configure($field);

        self::assertSame($expectedRenderedUrl, $fieldDto->getFormattedValue());
    }

    /**
     * @testWith ["javascript:alert(1)"]
     *           ["JavaScript:alert(1)"]
     *           ["JaVaScRiPt:alert(1)"]
     *           ["data:text/html,<script>alert(1)</script>"]
     *           ["vbscript:msgbox(1)"]
     *           ["file:///etc/passwd"]
     *           ["  javascript:alert(1)"]
     *           ["\tjavascript:alert(1)"]
     *           ["\njavascript:alert(1)"]
     *           ["\u0000javascript:alert(1)"]
     */
    public function testDangerousSchemesAreMarkedUnsafe(string $url): void
    {
        $this->initializeConfigurator();

        $field = UrlField::new('foo')->setValue($url);
        $fieldDto = $this->configure($field);

        self::assertTrue($fieldDto->getCustomOption(UrlField::OPTION_IS_UNSAFE));
    }

    /**
     * @testWith ["http://example.com"]
     *           ["https://example.com"]
     *           ["ftp://example.com"]
     *           ["ftps://example.com"]
     *           ["ssh://user@example.com"]
     *           ["sftp://user@example.com"]
     *           ["git://example.com/repo.git"]
     *           ["mailto:user@example.com"]
     *           ["webcal://example.com/cal.ics"]
     *           ["/relative/path"]
     *           ["relative/path"]
     *           ["//example.com/protocol-relative"]
     *           [""]
     */
    public function testSafeSchemesAreNotMarkedUnsafe(string $url): void
    {
        $this->initializeConfigurator();

        $field = UrlField::new('foo')->setValue($url);
        $fieldDto = $this->configure($field);

        self::assertFalse($fieldDto->getCustomOption(UrlField::OPTION_IS_UNSAFE));
    }

    public function testTemplateRendersDangerousUrlAsSpanInsteadOfLink(): void
    {
        $this->initializeConfigurator();

        $field = UrlField::new('foo')->setValue('javascript:alert(1)');
        $fieldDto = $this->configure($field);

        $html = $this->renderFieldTemplate($fieldDto, $this->entityDto, $this->adminContext);

        // the dangerous scheme must not be rendered inside an anchor's href attribute
        self::assertStringNotContainsString('<a ', $html);
        self::assertStringNotContainsString('href=', $html);
        self::assertStringContainsString('<span', $html);
        self::assertStringContainsString('javascript:alert(1)', $html);
    }

    public function testTemplateEscapesDangerousUrlContainingHtml(): void
    {
        $this->initializeConfigurator();

        $field = UrlField::new('foo')->setValue('data:text/html,<script>alert(1)</script>');
        $fieldDto = $this->configure($field);

        $html = $this->renderFieldTemplate($fieldDto, $this->entityDto, $this->adminContext);

        self::assertStringNotContainsString('<script>', $html);
        self::assertStringContainsString('&lt;script&gt;', $html);
    }

    public function testTemplateRendersSafeUrlAsLink(): void
    {
        $this->initializeConfigurator();

        $field = UrlField::new('foo')->setValue('https://example.com');
        $fieldDto = $this->configure($field);

        $html = $this->renderFieldTemplate($fieldDto, $this->entityDto, $this->adminContext);

        self::assertStringContainsString('<a ', $html);
        self::assertStringContainsString('href="https://example.com"', $html);
        self::assertStringContainsString('target="_blank"', $html);
        self::assertStringContainsString('rel="noopener"', $html);
    }

    public function testTemplateRendersDangerousUrlAsSpanOnDetailAction(): void
    {
        $this->initializeConfigurator();

        $field = UrlField::new('foo')->setValue('javascript:alert(1)');
        $fieldDto = $this->configure($field, pageName: Crud::PAGE_DETAIL, actionName: Action::DETAIL);

        $html = $this->renderFieldTemplate($fieldDto, $this->entityDto, $this->adminContext);

        self::assertStringNotContainsString('<a ', $html);
        self::assertStringContainsString('<span', $html);
    }

    public function testAllowedProtocolsOptionDefaultsToNull(): void
    {
        $this->initializeConfigurator();

        $field = UrlField::new('foo');
        $fieldDto = $this->configure($field, actionName: Action::EDIT);

        self::assertNull($fieldDto->getCustomOption(UrlField::OPTION_ALLOWED_PROTOCOLS));
        self::assertNull($fieldDto->getFormTypeOption('constraints'));
    }

    public function testAllowedProtocolsAddsUrlConstraint(): void
    {
        $this->initializeConfigurator();

        $field = UrlField::new('foo')->allowedProtocols(['http', 'https']);
        $fieldDto = $this->configure($field, actionName: Action::EDIT);

        self::assertSame(['http', 'https'], $fieldDto->getCustomOption(UrlField::OPTION_ALLOWED_PROTOCOLS));

        $constraints = $fieldDto->getFormTypeOption('constraints');
        self::assertIsArray($constraints);
        self::assertCount(1, $constraints);
        self::assertInstanceOf(Url::class, $constraints[0]);
        self::assertSame(['http', 'https'], $constraints[0]->protocols);
    }

    public function testAllowedProtocolsAppendsToExistingConstraints(): void
    {
        $this->initializeConfigurator();

        $existingConstraint = new NotBlank();
        $field = UrlField::new('foo')
            ->setFormTypeOption('constraints', [$existingConstraint])
            ->allowedProtocols(['ftp', 'sftp']);
        $fieldDto = $this->configure($field, actionName: Action::EDIT);

        $constraints = $fieldDto->getFormTypeOption('constraints');
        self::assertIsArray($constraints);
        self::assertCount(2, $constraints);
        self::assertSame($existingConstraint, $constraints[0]);
        self::assertInstanceOf(Url::class, $constraints[1]);
        self::assertSame(['ftp', 'sftp'], $constraints[1]->protocols);
    }

    public function testAllowedProtocolsReturnsSelfForFluentInterface(): void
    {
        $field = UrlField::new('foo');

        self::assertSame($field, $field->allowedProtocols(['http', 'https']));
    }

    /**
     * @testWith [["http"]]
     *           [["https"]]
     *           [["ftp", "ftps"]]
     *           [["ssh", "sftp", "git"]]
     *           [["http", "https", "ftp", "mailto", "webcal"]]
     */
    public function testAllowedProtocolsAcceptsDifferentProtocolSets(array $protocols): void
    {
        $this->initializeConfigurator();

        $field = UrlField::new('foo')->allowedProtocols($protocols);
        $fieldDto = $this->configure($field, actionName: Action::EDIT);

        self::assertSame($protocols, $fieldDto->getCustomOption(UrlField::OPTION_ALLOWED_PROTOCOLS));

        $constraints = $fieldDto->getFormTypeOption('constraints');
        self::assertCount(1, $constraints);
        self::assertInstanceOf(Url::class, $constraints[0]);
        self::assertSame($protocols, $constraints[0]->protocols);
    }

    public function testAllowedProtocolsWithEmptyArrayStillAddsConstraint(): void
    {
        $this->initializeConfigurator();

        $field = UrlField::new('foo')->allowedProtocols([]);
        $fieldDto = $this->configure($field, actionName: Action::EDIT);

        self::assertSame([], $fieldDto->getCustomOption(UrlField::OPTION_ALLOWED_PROTOCOLS));

        $constraints = $fieldDto->getFormTypeOption('constraints');
        self::assertCount(1, $constraints);
        self::assertInstanceOf(Url::class, $constraints[0]);
        self::assertSame([], $constraints[0]->protocols);
    }

    public function testAllowedProtocolsLastCallWins(): void
    {
        $this->initializeConfigurator();

        $field = UrlField::new('foo')
            ->allowedProtocols(['http', 'https'])
            ->allowedProtocols(['ftp']);
        $fieldDto = $this->configure($field, actionName: Action::EDIT);

        self::assertSame(['ftp'], $fieldDto->getCustomOption(UrlField::OPTION_ALLOWED_PROTOCOLS));

        $constraints = $fieldDto->getFormTypeOption('constraints');
        self::assertCount(1, $constraints);
        self::assertSame(['ftp'], $constraints[0]->protocols);
    }

    public function testAllowedProtocolsIsIndependentFromDefaultProtocol(): void
    {
        $this->initializeConfigurator();

        $field = UrlField::new('foo')
            ->setDefaultProtocol('https')
            ->allowedProtocols(['http', 'https']);
        $fieldDto = $this->configure($field, actionName: Action::EDIT);

        self::assertSame('https', $fieldDto->getCustomOption(UrlField::OPTION_DEFAULT_PROTOCOL));
        self::assertSame('https', $fieldDto->getFormTypeOption('default_protocol'));
        self::assertSame(['http', 'https'], $fieldDto->getCustomOption(UrlField::OPTION_ALLOWED_PROTOCOLS));
        self::assertCount(1, $fieldDto->getFormTypeOption('constraints'));
    }

    public function testNoConstraintIsAddedWhenAllowedProtocolsIsNotCalled(): void
    {
        $this->initializeConfigurator();

        $existingConstraint = new NotBlank();
        $field = UrlField::new('foo')
            ->setFormTypeOption('constraints', [$existingConstraint]);
        $fieldDto = $this->configure($field, actionName: Action::EDIT);

        $constraints = $fieldDto->getFormTypeOption('constraints');
        self::assertCount(1, $constraints);
        self::assertSame($existingConstraint, $constraints[0]);
    }

    private function initializeConfigurator(): void
    {
        self::bootKernel();
        $this->configurator = new UrlConfigurator();
    }
}
