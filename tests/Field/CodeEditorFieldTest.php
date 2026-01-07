<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Field;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\CodeEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\CodeEditorType;

class CodeEditorFieldTest extends AbstractFieldTest
{
    protected function setUp(): void
    {
        parent::setUp();

        // CodeEditorField has no dedicated configurator, but we need to set formattedValue
        $this->configurator = new class implements FieldConfiguratorInterface {
            public function supports(FieldDto $field, EntityDto $entityDto): bool
            {
                return CodeEditorField::class === $field->getFieldFqcn();
            }

            public function configure(FieldDto $field, EntityDto $entityDto, AdminContext $context): void
            {
                // set formattedValue to value (like CommonPreConfigurator does for most fields)
                if (null === $field->getFormattedValue()) {
                    $field->setFormattedValue($field->getValue());
                }
            }
        };
    }

    public function testDefaultOptions(): void
    {
        $field = CodeEditorField::new('code');
        $fieldDto = $this->configure($field);

        self::assertFalse($fieldDto->getCustomOption(CodeEditorField::OPTION_INDENT_WITH_TABS));
        self::assertSame('markdown', $fieldDto->getCustomOption(CodeEditorField::OPTION_LANGUAGE));
        self::assertNull($fieldDto->getCustomOption(CodeEditorField::OPTION_NUM_OF_ROWS));
        self::assertSame(4, $fieldDto->getCustomOption(CodeEditorField::OPTION_TAB_SIZE));
        self::assertTrue($fieldDto->getCustomOption(CodeEditorField::OPTION_SHOW_LINE_NUMBERS));
        self::assertSame(CodeEditorType::class, $fieldDto->getFormType());
        self::assertStringContainsString('field-code_editor', $fieldDto->getCssClass());
    }

    public function testFieldWithNullValue(): void
    {
        $field = CodeEditorField::new('code');
        $field->setValue(null);
        $fieldDto = $this->configure($field);

        self::assertNull($fieldDto->getValue());
    }

    public function testFieldWithCodeValue(): void
    {
        $code = "function hello() {\n    console.log('Hello');\n}";
        $field = CodeEditorField::new('code');
        $field->setValue($code);
        $fieldDto = $this->configure($field);

        self::assertSame($code, $fieldDto->getValue());
    }

    public function testSetIndentWithTabs(): void
    {
        $field = CodeEditorField::new('code');
        $field->setIndentWithTabs(true);
        $fieldDto = $this->configure($field);

        self::assertTrue($fieldDto->getCustomOption(CodeEditorField::OPTION_INDENT_WITH_TABS));
    }

    public function testSetIndentWithSpaces(): void
    {
        $field = CodeEditorField::new('code');
        $field->setIndentWithTabs(false);
        $fieldDto = $this->configure($field);

        self::assertFalse($fieldDto->getCustomOption(CodeEditorField::OPTION_INDENT_WITH_TABS));
    }

    /**
     * @testWith ["css"]
     *           ["dockerfile"]
     *           ["js"]
     *           ["javascript"]
     *           ["markdown"]
     *           ["nginx"]
     *           ["php"]
     *           ["shell"]
     *           ["sql"]
     *           ["twig"]
     *           ["xml"]
     *           ["yaml-frontmatter"]
     *           ["yaml"]
     */
    public function testSetLanguage(string $language): void
    {
        $field = CodeEditorField::new('code');
        $field->setLanguage($language);
        $fieldDto = $this->configure($field);

        self::assertSame($language, $fieldDto->getCustomOption(CodeEditorField::OPTION_LANGUAGE));
    }

    public function testSetLanguageThrowsExceptionForInvalidLanguage(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        CodeEditorField::new('code')->setLanguage('invalid_language');
    }

    public function testSetNumOfRows(): void
    {
        $field = CodeEditorField::new('code');
        $field->setNumOfRows(20);
        $fieldDto = $this->configure($field);

        self::assertSame(20, $fieldDto->getCustomOption(CodeEditorField::OPTION_NUM_OF_ROWS));
    }

    public function testSetNumOfRowsThrowsExceptionForZeroOrNegative(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        CodeEditorField::new('code')->setNumOfRows(0);
    }

    public function testSetTabSize(): void
    {
        $field = CodeEditorField::new('code');
        $field->setTabSize(2);
        $fieldDto = $this->configure($field);

        self::assertSame(2, $fieldDto->getCustomOption(CodeEditorField::OPTION_TAB_SIZE));
    }

    public function testSetTabSizeThrowsExceptionForZeroOrNegative(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        CodeEditorField::new('code')->setTabSize(0);
    }

    public function testHideLineNumbers(): void
    {
        $field = CodeEditorField::new('code');
        $field->hideLineNumbers(true);
        $fieldDto = $this->configure($field);

        self::assertFalse($fieldDto->getCustomOption(CodeEditorField::OPTION_SHOW_LINE_NUMBERS));
    }

    public function testShowLineNumbers(): void
    {
        $field = CodeEditorField::new('code');
        $field->hideLineNumbers(false);
        $fieldDto = $this->configure($field);

        self::assertTrue($fieldDto->getCustomOption(CodeEditorField::OPTION_SHOW_LINE_NUMBERS));
    }

    public function testTemplateRendersCodeEditorOnDetailPage(): void
    {
        $code = 'console.log("Hello");';
        $field = CodeEditorField::new('code');
        $field->setValue($code);
        $field->setLanguage('javascript');
        $fieldDto = $this->configure($field, Crud::PAGE_DETAIL, 'en', Action::DETAIL);

        $html = $this->renderFieldTemplate($fieldDto, $this->entityDto, $this->adminContext);

        self::assertStringContainsString('data-ea-code-editor-field="true"', $html);
        self::assertStringContainsString('data-language="javascript"', $html);
        // the code is HTML-escaped in the template
        self::assertStringContainsString('console.log', $html);
        self::assertStringContainsString('Hello', $html);
    }

    /**
     * Note: Testing the index page template is skipped because it uses the Icon component
     * which requires a fully initialized AdminContext with AssetsDto. The detail page
     * template tests above are sufficient to verify the core code editor rendering.
     */
    public function testTemplateRespectsLanguageSetting(): void
    {
        $code = 'SELECT * FROM users;';
        $field = CodeEditorField::new('code');
        $field->setValue($code);
        $field->setLanguage('sql');
        $fieldDto = $this->configure($field, Crud::PAGE_DETAIL, 'en', Action::DETAIL);

        $html = $this->renderFieldTemplate($fieldDto, $this->entityDto, $this->adminContext);

        self::assertStringContainsString('data-language="sql"', $html);
    }

    public function testTemplateRespectsTabSizeSetting(): void
    {
        $field = CodeEditorField::new('code');
        $field->setValue('code');
        $field->setTabSize(2);
        $fieldDto = $this->configure($field, Crud::PAGE_DETAIL, 'en', Action::DETAIL);

        $html = $this->renderFieldTemplate($fieldDto, $this->entityDto, $this->adminContext);

        self::assertStringContainsString('data-tab-size="2"', $html);
    }

    public function testTemplateRespectsIndentWithTabsSetting(): void
    {
        $field = CodeEditorField::new('code');
        $field->setValue('code');
        $field->setIndentWithTabs(true);
        $fieldDto = $this->configure($field, Crud::PAGE_DETAIL, 'en', Action::DETAIL);

        $html = $this->renderFieldTemplate($fieldDto, $this->entityDto, $this->adminContext);

        self::assertStringContainsString('data-indent-with-tabs="true"', $html);
    }

    public function testTemplateRespectsShowLineNumbersSetting(): void
    {
        $field = CodeEditorField::new('code');
        $field->setValue('code');
        $field->hideLineNumbers(true);
        $fieldDto = $this->configure($field, Crud::PAGE_DETAIL, 'en', Action::DETAIL);

        $html = $this->renderFieldTemplate($fieldDto, $this->entityDto, $this->adminContext);

        self::assertStringContainsString('data-show-line-numbers="false"', $html);
    }
}
