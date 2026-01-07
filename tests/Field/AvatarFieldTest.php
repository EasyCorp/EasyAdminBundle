<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Field;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\Size;
use EasyCorp\Bundle\EasyAdminBundle\Field\AvatarField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\AvatarConfigurator;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class AvatarFieldTest extends AbstractFieldTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->configurator = new AvatarConfigurator();
    }

    public function testDefaultOptions(): void
    {
        $field = AvatarField::new('avatar');
        $fieldDto = $this->configure($field);

        self::assertFalse($fieldDto->getCustomOption(AvatarField::OPTION_IS_GRAVATAR_EMAIL));
        // Default height is set by the configurator (24 on index, 48 on detail)
        self::assertSame(24, $fieldDto->getCustomOption(AvatarField::OPTION_HEIGHT));
        self::assertSame(TextType::class, $fieldDto->getFormType());
        self::assertStringContainsString('field-avatar', $fieldDto->getCssClass());
    }

    public function testDefaultHeightOnIndexPage(): void
    {
        $field = AvatarField::new('avatar');
        $fieldDto = $this->configure($field, Crud::PAGE_INDEX);

        // On index page, default height is 24
        self::assertSame(24, $fieldDto->getCustomOption(AvatarField::OPTION_HEIGHT));
    }

    public function testDefaultHeightOnDetailPage(): void
    {
        $field = AvatarField::new('avatar');
        $fieldDto = $this->configure($field, Crud::PAGE_DETAIL, 'en', Action::DETAIL);

        // On detail page, default height is 48
        self::assertSame(48, $fieldDto->getCustomOption(AvatarField::OPTION_HEIGHT));
    }

    public function testSetHeightWithInteger(): void
    {
        $field = AvatarField::new('avatar');
        $field->setHeight(100);
        $fieldDto = $this->configure($field);

        self::assertSame(100, $fieldDto->getCustomOption(AvatarField::OPTION_HEIGHT));
    }

    public function testSetHeightWithSemanticSizeSmall(): void
    {
        $field = AvatarField::new('avatar');
        $field->setHeight(Size::SM);
        $fieldDto = $this->configure($field);

        self::assertSame(18, $fieldDto->getCustomOption(AvatarField::OPTION_HEIGHT));
    }

    public function testSetHeightWithSemanticSizeMedium(): void
    {
        $field = AvatarField::new('avatar');
        $field->setHeight(Size::MD);
        $fieldDto = $this->configure($field);

        self::assertSame(24, $fieldDto->getCustomOption(AvatarField::OPTION_HEIGHT));
    }

    public function testSetHeightWithSemanticSizeLarge(): void
    {
        $field = AvatarField::new('avatar');
        $field->setHeight(Size::LG);
        $fieldDto = $this->configure($field);

        self::assertSame(48, $fieldDto->getCustomOption(AvatarField::OPTION_HEIGHT));
    }

    public function testSetHeightWithSemanticSizeExtraLarge(): void
    {
        $field = AvatarField::new('avatar');
        $field->setHeight(Size::XL);
        $fieldDto = $this->configure($field);

        self::assertSame(96, $fieldDto->getCustomOption(AvatarField::OPTION_HEIGHT));
    }

    public function testSetHeightThrowsExceptionForZeroOrNegative(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        AvatarField::new('avatar')->setHeight(0);
    }

    public function testSetIsGravatarEmail(): void
    {
        $field = AvatarField::new('avatar');
        $field->setIsGravatarEmail();
        $field->setValue('test@example.com');
        $fieldDto = $this->configure($field);

        self::assertTrue($fieldDto->getCustomOption(AvatarField::OPTION_IS_GRAVATAR_EMAIL));
    }

    public function testGravatarUrlGeneration(): void
    {
        $field = AvatarField::new('avatar');
        $field->setIsGravatarEmail(true);
        $field->setValue('test@example.com');
        $fieldDto = $this->configure($field);

        $formattedValue = $fieldDto->getFormattedValue();
        self::assertStringStartsWith('https://www.gravatar.com/avatar/', $formattedValue);
        self::assertStringContainsString(md5('test@example.com'), $formattedValue);
    }

    public function testSetGravatarDefaultImage(): void
    {
        $field = AvatarField::new('avatar');
        $field->setGravatarDefaultImage('identicon');
        $fieldDto = $this->configure($field);

        self::assertSame('identicon', $fieldDto->getCustomOption(AvatarField::OPTION_GRAVATAR_DEFAULT_IMAGE));
    }

    public function testSetGravatarDefaultImageWithUrl(): void
    {
        $field = AvatarField::new('avatar');
        $field->setGravatarDefaultImage('https://example.com/default.png');
        $fieldDto = $this->configure($field);

        self::assertSame('https://example.com/default.png', $fieldDto->getCustomOption(AvatarField::OPTION_GRAVATAR_DEFAULT_IMAGE));
    }

    public function testSetGravatarDefaultImageThrowsExceptionForInvalidValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        AvatarField::new('avatar')->setGravatarDefaultImage('invalid_value');
    }

    public function testGravatarDefaultImageInUrl(): void
    {
        $field = AvatarField::new('avatar');
        $field->setIsGravatarEmail(true);
        $field->setValue('test@example.com');
        $field->setGravatarDefaultImage('identicon');
        $fieldDto = $this->configure($field);

        $formattedValue = $fieldDto->getFormattedValue();
        self::assertStringContainsString('d=identicon', $formattedValue);
    }

    public function testDefaultGravatarDefaultImage(): void
    {
        $field = AvatarField::new('avatar');
        $field->setIsGravatarEmail(true);
        $field->setValue('test@example.com');
        $fieldDto = $this->configure($field);

        // Default gravatar image is 'mp'
        $formattedValue = $fieldDto->getFormattedValue();
        self::assertStringContainsString('d=mp', $formattedValue);
    }
}
