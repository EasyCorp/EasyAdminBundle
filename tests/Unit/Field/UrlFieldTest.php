<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Field;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\UrlConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Url;

class UrlFieldTest extends AbstractFieldTestCase
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

    #[TestWith([''])]
    #[TestWith(['http'])]
    #[TestWith(['https'])]
    #[TestWith(['ftp'])]
    public function testDefaultProtocolOption(string $defaultProtocol): void
    {
        $this->initializeConfigurator();

        $field = UrlField::new('foo');
        $field->setDefaultProtocol($defaultProtocol);
        $fieldDto = $this->configure($field, actionName: Action::EDIT);

        self::assertSame($defaultProtocol, $fieldDto->getCustomOption(UrlField::OPTION_DEFAULT_PROTOCOL));
        self::assertSame($defaultProtocol, $fieldDto->getFormTypeOption('default_protocol'));
    }

    #[TestWith(['http://example.com', 'example.com'])]
    #[TestWith(['https://example.com', 'example.com'])]
    #[TestWith(['http://www.example.com', 'example.com'])]
    #[TestWith(['https://www.example.com', 'example.com'])]
    #[TestWith(['https://01234567890123456789012345678901234567890123456789.com', '0123456789012345678901234567890…'])]
    public function testFormattedValuesOnIndexAction(string $url, string $expectedRenderedUrl): void
    {
        $this->initializeConfigurator();

        $field = UrlField::new('foo')->setValue($url);
        $fieldDto = $this->configure($field);

        self::assertSame($expectedRenderedUrl, $fieldDto->getFormattedValue());
    }

    #[TestWith(['javascript:alert(1)'])]
    #[TestWith(['JavaScript:alert(1)'])]
    #[TestWith(['JaVaScRiPt:alert(1)'])]
    #[TestWith(['data:text/html,<script>alert(1)</script>'])]
    #[TestWith(['vbscript:msgbox(1)'])]
    #[TestWith(['file:///etc/passwd'])]
    #[TestWith(['  javascript:alert(1)'])]
    #[TestWith(["\tjavascript:alert(1)"])]
    #[TestWith(["\njavascript:alert(1)"])]
    #[TestWith(["\x00javascript:alert(1)"])]
    public function testDangerousSchemesAreMarkedUnsafe(string $url): void
    {
        $this->initializeConfigurator();

        $field = UrlField::new('foo')->setValue($url);
        $fieldDto = $this->configure($field);

        self::assertTrue($fieldDto->getCustomOption(UrlField::OPTION_IS_UNSAFE));
    }

    #[TestWith(['http://example.com'])]
    #[TestWith(['https://example.com'])]
    #[TestWith(['ftp://example.com'])]
    #[TestWith(['ftps://example.com'])]
    #[TestWith(['ssh://user@example.com'])]
    #[TestWith(['sftp://user@example.com'])]
    #[TestWith(['git://example.com/repo.git'])]
    #[TestWith(['mailto:user@example.com'])]
    #[TestWith(['webcal://example.com/cal.ics'])]
    #[TestWith(['/relative/path'])]
    #[TestWith(['relative/path'])]
    #[TestWith(['//example.com/protocol-relative'])]
    #[TestWith([''])]
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

    #[TestWith([['http']])]
    #[TestWith([['https']])]
    #[TestWith([['ftp', 'ftps']])]
    #[TestWith([['ssh', 'sftp', 'git']])]
    #[TestWith([['http', 'https', 'ftp', 'mailto', 'webcal']])]
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
