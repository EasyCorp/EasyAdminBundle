<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Field;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\BooleanConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class BooleanFieldTest extends AbstractFieldTest
{
    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        // create mock dependencies for the BooleanConfigurator
        $authChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authChecker->method('isGranted')->willReturn(false);

        $csrfTokenManager = $this->createMock(CsrfTokenManagerInterface::class);

        $adminUrlGenerator = static::getContainer()->get(AdminUrlGenerator::class);

        $this->configurator = new BooleanConfigurator($adminUrlGenerator, $authChecker, $csrfTokenManager);
    }

    public function testDefaultOptions(): void
    {
        $field = BooleanField::new('active');
        $fieldDto = $this->configure($field);

        self::assertTrue($fieldDto->getCustomOption(BooleanField::OPTION_RENDER_AS_SWITCH));
        self::assertFalse($fieldDto->getCustomOption(BooleanField::OPTION_HIDE_VALUE_WHEN_TRUE));
        self::assertFalse($fieldDto->getCustomOption(BooleanField::OPTION_HIDE_VALUE_WHEN_FALSE));
        self::assertSame(CheckboxType::class, $fieldDto->getFormType());
        self::assertStringContainsString('field-boolean', $fieldDto->getCssClass());
    }

    /**
     * @testWith [true]
     *           [false]
     *           [null]
     */
    public function testFieldValue(?bool $value): void
    {
        $field = BooleanField::new('active');
        $field->setValue($value);
        $fieldDto = $this->configure($field);

        self::assertSame($value, $fieldDto->getValue());
    }

    public function testRenderAsSwitch(): void
    {
        $field = BooleanField::new('active');
        $field->renderAsSwitch();
        $fieldDto = $this->configure($field);

        self::assertTrue($fieldDto->getCustomOption(BooleanField::OPTION_RENDER_AS_SWITCH));
        self::assertStringContainsString('has-switch', $fieldDto->getCssClass());
    }

    public function testRenderNotAsSwitch(): void
    {
        $field = BooleanField::new('active');
        $field->renderAsSwitch(false);
        $fieldDto = $this->configure($field);

        self::assertFalse($fieldDto->getCustomOption(BooleanField::OPTION_RENDER_AS_SWITCH));
        self::assertStringNotContainsString('has-switch', $fieldDto->getCssClass());
    }

    public function testHideValueWhenTrue(): void
    {
        $field = BooleanField::new('active');
        $field->hideValueWhenTrue();
        $fieldDto = $this->configure($field);

        self::assertTrue($fieldDto->getCustomOption(BooleanField::OPTION_HIDE_VALUE_WHEN_TRUE));
    }

    public function testDontHideValueWhenTrue(): void
    {
        $field = BooleanField::new('active');
        $field->hideValueWhenTrue(false);
        $fieldDto = $this->configure($field);

        self::assertFalse($fieldDto->getCustomOption(BooleanField::OPTION_HIDE_VALUE_WHEN_TRUE));
    }

    public function testHideValueWhenFalse(): void
    {
        $field = BooleanField::new('active');
        $field->hideValueWhenFalse(true);
        $fieldDto = $this->configure($field);

        self::assertTrue($fieldDto->getCustomOption(BooleanField::OPTION_HIDE_VALUE_WHEN_FALSE));
    }

    public function testDontHideValueWhenFalse(): void
    {
        $field = BooleanField::new('active');
        $field->hideValueWhenFalse(false);
        $fieldDto = $this->configure($field);

        self::assertFalse($fieldDto->getCustomOption(BooleanField::OPTION_HIDE_VALUE_WHEN_FALSE));
    }

    public function testSwitchAddsCssClass(): void
    {
        $field = BooleanField::new('active');
        $field->renderAsSwitch();
        $fieldDto = $this->configure($field);

        self::assertSame('checkbox-switch', $fieldDto->getFormTypeOption('label_attr.class'));
    }

    public function testTemplateRendersSwitchOnIndexPage(): void
    {
        $field = BooleanField::new('active');
        $field->setValue(true);
        $field->renderAsSwitch();
        $fieldDto = $this->configure($field);

        $html = $this->renderFieldTemplate($fieldDto, $this->entityDto, $this->adminContext);

        self::assertStringContainsString('ea-switch', $html);
        self::assertStringContainsString('<input type="checkbox"', $html);
        self::assertStringContainsString('checked', $html);
    }

    public function testTemplateRendersBadgeOnDetailPage(): void
    {
        $field = BooleanField::new('active');
        $field->setValue(true);
        $field->renderAsSwitch(true);
        $fieldDto = $this->configure($field, Crud::PAGE_DETAIL, 'en', Action::DETAIL);

        $html = $this->renderFieldTemplate($fieldDto, $this->entityDto, $this->adminContext);

        // on detail page, even with renderAsSwitch=true, it shows a badge
        self::assertStringContainsString('badge', $html);
        self::assertStringContainsString('badge-boolean-true', $html);
        self::assertStringNotContainsString('ea-switch', $html);
    }

    public function testTemplateRendersBadgeWhenNotSwitch(): void
    {
        $field = BooleanField::new('active');
        $field->setValue(false);
        $field->renderAsSwitch(false);
        $fieldDto = $this->configure($field);

        $html = $this->renderFieldTemplate($fieldDto, $this->entityDto, $this->adminContext);

        self::assertStringContainsString('badge', $html);
        self::assertStringContainsString('badge-boolean-false', $html);
        self::assertStringNotContainsString('ea-switch', $html);
    }

    public function testTemplateHidesValueWhenTrueAndOptionEnabled(): void
    {
        $field = BooleanField::new('active');
        $field->setValue(true);
        $field->renderAsSwitch(false);
        $field->hideValueWhenTrue();
        $fieldDto = $this->configure($field);

        $html = $this->renderFieldTemplate($fieldDto, $this->entityDto, $this->adminContext);

        // badge should be hidden when value is true and hideValueWhenTrue is enabled (on index page)
        self::assertStringNotContainsString('badge-boolean-true', $html);
    }

    public function testTemplateHidesValueWhenFalseAndOptionEnabled(): void
    {
        $field = BooleanField::new('active');
        $field->setValue(false);
        $field->renderAsSwitch(false);
        $field->hideValueWhenFalse();
        $fieldDto = $this->configure($field);

        $html = $this->renderFieldTemplate($fieldDto, $this->entityDto, $this->adminContext);

        // badge should be hidden when value is false and hideValueWhenFalse is enabled (on index page)
        self::assertStringNotContainsString('badge-boolean-false', $html);
    }

    public function testTemplateShowsValueWhenTrueAndHideOptionDisabled(): void
    {
        $field = BooleanField::new('active');
        $field->setValue(true);
        $field->renderAsSwitch(false);
        $field->hideValueWhenTrue(false);
        $fieldDto = $this->configure($field);

        $html = $this->renderFieldTemplate($fieldDto, $this->entityDto, $this->adminContext);

        self::assertStringContainsString('badge-boolean-true', $html);
    }

    public function testTemplateSwitchNotCheckedWhenFalse(): void
    {
        $field = BooleanField::new('active');
        $field->setValue(false);
        $field->renderAsSwitch();
        $fieldDto = $this->configure($field);

        $html = $this->renderFieldTemplate($fieldDto, $this->entityDto, $this->adminContext);

        self::assertStringContainsString('ea-switch', $html);
        self::assertStringContainsString('<input type="checkbox"', $html);
        self::assertStringNotContainsString('checked', $html);
    }

    public function testColoredSwitchHelpersSetVariantAndForceSwitch(): void
    {
        $variants = [
            'success' => static fn (BooleanField $field) => $field->renderAsSuccessSwitch(),
            'warning' => static fn (BooleanField $field) => $field->renderAsWarningSwitch(),
            'danger' => static fn (BooleanField $field) => $field->renderAsDangerSwitch(),
        ];

        foreach ($variants as $expectedVariant => $applyHelper) {
            $field = BooleanField::new('active');
            $applyHelper($field);
            $fieldDto = $this->configure($field);

            self::assertTrue($fieldDto->getCustomOption(BooleanField::OPTION_RENDER_AS_SWITCH));
            self::assertSame($expectedVariant, $fieldDto->getCustomOption(BooleanField::OPTION_SWITCH_VARIANT));
        }
    }

    public function testSwapLabelAndValueByDefault(): void
    {
        $field = BooleanField::new('active');
        $fieldDto = $this->configure($field);

        self::assertTrue($fieldDto->getCustomOption(BooleanField::OPTION_SWAP_LABEL_AND_VALUE));
        self::assertStringNotContainsString('field-boolean-no-swap', $fieldDto->getCssClass());
    }

    public function testDontSwapLabelAndValue(): void
    {
        $field = BooleanField::new('active');
        $field->swapLabelAndValue(false);
        $fieldDto = $this->configure($field);

        self::assertFalse($fieldDto->getCustomOption(BooleanField::OPTION_SWAP_LABEL_AND_VALUE));
        self::assertStringContainsString('field-boolean-no-swap', $fieldDto->getCssClass());
    }

    public function testColoredSwitchAddsVariantLabelClass(): void
    {
        $field = BooleanField::new('active');
        $field->renderAsSuccessSwitch();
        $fieldDto = $this->configure($field);

        self::assertSame('checkbox-switch checkbox-switch-success', $fieldDto->getFormTypeOption('label_attr.class'));
    }

    public function testTemplateRendersSwitchVariantClassOnIndexPage(): void
    {
        $field = BooleanField::new('active');
        $field->setValue(true);
        $field->renderAsDangerSwitch();
        $fieldDto = $this->configure($field);

        $html = $this->renderFieldTemplate($fieldDto, $this->entityDto, $this->adminContext);

        self::assertStringContainsString('ea-switch', $html);
        self::assertStringContainsString('ea-switch-danger', $html);
    }
}
