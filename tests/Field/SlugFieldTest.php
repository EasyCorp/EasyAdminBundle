<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Field;

use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\SlugConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\SlugType;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatableInterface;
use function Symfony\Component\Translation\t;

class SlugFieldTest extends AbstractFieldTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->configurator = new SlugConfigurator();
    }

    public function testDefaultOptions(): void
    {
        $field = SlugField::new('slug');
        // we need to set target field for the configurator to work
        $field->setTargetFieldName('title');
        $fieldDto = $this->configure($field);

        self::assertSame(SlugType::class, $fieldDto->getFormType());
        self::assertStringContainsString('field-text', $fieldDto->getCssClass());
        self::assertSame('crud/field/text', $fieldDto->getTemplateName());
    }

    public function testFieldWithNullValue(): void
    {
        $field = SlugField::new('slug');
        $field->setTargetFieldName('title');
        $field->setValue(null);
        $fieldDto = $this->configure($field);

        self::assertNull($fieldDto->getValue());
    }

    public function testFieldWithStringValue(): void
    {
        $field = SlugField::new('slug');
        $field->setTargetFieldName('title');
        $field->setValue('my-slug-value');
        $fieldDto = $this->configure($field);

        self::assertSame('my-slug-value', $fieldDto->getValue());
    }

    public function testSetTargetFieldNameWithString(): void
    {
        $field = SlugField::new('slug');
        $field->setTargetFieldName('title');
        $fieldDto = $this->configure($field);

        self::assertSame(['title'], $fieldDto->getCustomOption(SlugField::OPTION_TARGET_FIELD_NAME));
        self::assertSame('title', $fieldDto->getFormTypeOption('target'));
    }

    public function testSetTargetFieldNameWithArray(): void
    {
        $field = SlugField::new('slug');
        $field->setTargetFieldName(['title', 'subtitle']);
        $fieldDto = $this->configure($field);

        self::assertSame(['title', 'subtitle'], $fieldDto->getCustomOption(SlugField::OPTION_TARGET_FIELD_NAME));
        self::assertSame('title|subtitle', $fieldDto->getFormTypeOption('target'));
    }

    public function testWithoutTargetFieldNameThrowsException(): void
    {
        $this->expectException(\RuntimeException::class);

        $field = SlugField::new('slug');
        $this->configure($field);
    }

    public function testSetUnlockConfirmationMessage(): void
    {
        $field = SlugField::new('slug');
        $field->setTargetFieldName('title');
        $field->setUnlockConfirmationMessage('Are you sure?');
        $fieldDto = $this->configure($field);

        self::assertSame('Are you sure?', $fieldDto->getCustomOption(SlugField::OPTION_UNLOCK_CONFIRMATION_MESSAGE));
    }

    public function testUnlockConfirmationMessageInFormTypeOptions(): void
    {
        $field = SlugField::new('slug');
        $field->setTargetFieldName('title');
        $field->setUnlockConfirmationMessage('Unlock confirmation');
        $fieldDto = $this->configure($field);

        // the confirmation message should be set as a data attribute
        $confirmText = $fieldDto->getFormTypeOption('attr.data-confirm-text');
        self::assertNotNull($confirmText);
    }

    public function testSetUnlockConfirmationMessageWithTranslatableInterface(): void
    {
        $field = SlugField::new('slug');
        $field->setTargetFieldName('title');
        $field->setUnlockConfirmationMessage(t('unlock.confirmation'));
        $fieldDto = $this->configure($field);

        $customOption = $fieldDto->getCustomOption(SlugField::OPTION_UNLOCK_CONFIRMATION_MESSAGE);
        self::assertInstanceOf(TranslatableInterface::class, $customOption);
    }

    public function testUnlockConfirmationMessageWithSpecialCharacters(): void
    {
        $message = 'Are you sure? This <b>cannot</b> be "undone" & will change the URL\'s permanently!';
        $field = SlugField::new('slug');
        $field->setTargetFieldName('title');
        $field->setUnlockConfirmationMessage($message);
        $fieldDto = $this->configure($field);

        self::assertSame($message, $fieldDto->getCustomOption(SlugField::OPTION_UNLOCK_CONFIRMATION_MESSAGE));
        // the configurator wraps the message in a TranslatableMessage
        $formTypeOption = $fieldDto->getFormTypeOption('attr.data-confirm-text');
        self::assertInstanceOf(TranslatableMessage::class, $formTypeOption);
        self::assertSame($message, $formTypeOption->getMessage());
    }
}
