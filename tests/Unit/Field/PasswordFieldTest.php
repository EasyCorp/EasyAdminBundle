<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Field;

use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\PasswordConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\PasswordField;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

class PasswordFieldTest extends AbstractFieldTest
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->configurator = new PasswordConfigurator();
    }

    public function testDefaultOptions(): void
    {
        $field = PasswordField::new('foo');
        $fieldDto = $this->configure($field);

        self::assertSame(PasswordType::class, $fieldDto->getFormType());
        self::assertStringContainsString('field-password', $fieldDto->getCssClass());
        self::assertSame('crud/field/password', $fieldDto->getTemplateName());
        self::assertNull($fieldDto->getCustomOption(PasswordField::OPTION_HASH_PASSWORD));
    }

    public function testFieldWithNullValue(): void
    {
        $field = PasswordField::new('foo');
        $field->setValue(null);
        $fieldDto = $this->configure($field);

        self::assertNull($fieldDto->getValue());
    }

    public function testFieldWithStringValue(): void
    {
        $field = PasswordField::new('foo');
        $field->setValue('my_secret_password');
        $fieldDto = $this->configure($field);

        self::assertSame('my_secret_password', $fieldDto->getValue());
    }

    public function testHashPasswordOption(): void
    {
        $hashFunction = static fn (string $password) => md5($password);
        $field = PasswordField::new('foo')->hashPassword($hashFunction);
        $fieldDto = $this->configure($field);

        self::assertSame($hashFunction, $fieldDto->getCustomOption(PasswordField::OPTION_HASH_PASSWORD));
        self::assertFalse($fieldDto->getFormTypeOption('mapped'));
        self::assertNotNull($fieldDto->getFormTypeOption('builder_callable'));
    }
}
