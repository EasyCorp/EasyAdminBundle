<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Dto;

use EasyCorp\Bundle\EasyAdminBundle\Dto\ActionDto;
use EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Option\ButtonColor;
use EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Option\ButtonStyle;
use EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Option\ButtonVariant;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\TranslatableMessage;

class ActionDtoTest extends TestCase
{
    public function testConfirmationMessageDefaultValue(): void
    {
        $actionDto = new ActionDto();

        $this->assertFalse($actionDto->hasConfirmation());
        $this->assertFalse($actionDto->getConfirmationMessage());
    }

    public function testConfirmationMessageWithBooleanTrue(): void
    {
        $actionDto = new ActionDto();
        $actionDto->setConfirmationMessage(true);

        $this->assertTrue($actionDto->hasConfirmation());
        $this->assertTrue($actionDto->getConfirmationMessage());
    }

    public function testConfirmationMessageWithBooleanFalse(): void
    {
        $actionDto = new ActionDto();
        $actionDto->setConfirmationMessage(true);
        $actionDto->setConfirmationMessage(false);

        $this->assertFalse($actionDto->hasConfirmation());
        $this->assertFalse($actionDto->getConfirmationMessage());
    }

    public function testConfirmationMessageWithCustomString(): void
    {
        $actionDto = new ActionDto();
        $customMessage = 'Are you sure you want to %action_name% this %entity_name%?';
        $actionDto->setConfirmationMessage($customMessage);

        $this->assertTrue($actionDto->hasConfirmation());
        $this->assertSame($customMessage, $actionDto->getConfirmationMessage());
    }

    public function testConfirmationMessageWithTranslatableMessage(): void
    {
        $actionDto = new ActionDto();
        $translatableMessage = new TranslatableMessage('action.confirm.message');
        $actionDto->setConfirmationMessage($translatableMessage);

        $this->assertTrue($actionDto->hasConfirmation());
        $this->assertSame($translatableMessage, $actionDto->getConfirmationMessage());
    }

    public function testDisplayableConfirmationMessageDefaultValue(): void
    {
        $actionDto = new ActionDto();

        $this->assertNull($actionDto->getDisplayableConfirmationMessage());
    }

    public function testDisplayableConfirmationMessageWithString(): void
    {
        $actionDto = new ActionDto();
        $message = 'Custom confirmation message';
        $actionDto->setDisplayableConfirmationMessage($message);

        $this->assertSame($message, $actionDto->getDisplayableConfirmationMessage());
    }

    public function testDisplayableConfirmationMessageWithTranslatableMessage(): void
    {
        $actionDto = new ActionDto();
        $translatableMessage = new TranslatableMessage('action.confirm.displayable');
        $actionDto->setDisplayableConfirmationMessage($translatableMessage);

        $this->assertSame($translatableMessage, $actionDto->getDisplayableConfirmationMessage());
    }

    public function testConfirmationButtonLabelDefaultValue(): void
    {
        $actionDto = new ActionDto();

        $this->assertNull($actionDto->getConfirmationButtonLabel());
    }

    public function testConfirmationButtonLabelWithString(): void
    {
        $actionDto = new ActionDto();
        $actionDto->setConfirmationButtonLabel('Accept');

        $this->assertSame('Accept', $actionDto->getConfirmationButtonLabel());
    }

    public function testConfirmationButtonLabelWithTranslatableMessage(): void
    {
        $actionDto = new ActionDto();
        $translatableButton = new TranslatableMessage('action.accept.button');
        $actionDto->setConfirmationButtonLabel($translatableButton);

        $this->assertSame($translatableButton, $actionDto->getConfirmationButtonLabel());
    }

    #[DataProvider('provideColorsAndVariants')]
    public function testColorAndButtonVariant(?ButtonColor $color, ?ButtonStyle $style, string $cssClass, string $addedCssClass, ButtonColor $expectedColor, ButtonVariant $expectedButtonVariant): void
    {
        $actionDto = new ActionDto();
        $actionDto->setColor($color ?? ButtonColor::Default);
        $actionDto->setStyle($style ?? ButtonStyle::Solid);
        $actionDto->setCssClass($cssClass);
        $actionDto->setAddedCssClass($addedCssClass);

        $this->assertSame($expectedColor, $actionDto->getColor());
        $this->assertSame($expectedButtonVariant, $actionDto->getButtonVariant());
    }

    public static function provideColorsAndVariants(): iterable
    {
        yield 'default action' => [null, null, '', '', ButtonColor::Default, ButtonVariant::Outline];
        yield 'primary action' => [ButtonColor::Primary, null, '', '', ButtonColor::Primary, ButtonVariant::Solid];
        yield 'danger action' => [ButtonColor::Danger, null, '', '', ButtonColor::Danger, ButtonVariant::Light];
        yield 'text danger action' => [ButtonColor::Danger, ButtonStyle::Text, '', '', ButtonColor::Danger, ButtonVariant::Ghost];
        yield 'color from addCssClass()' => [null, null, 'action-foo', 'btn btn-primary', ButtonColor::Primary, ButtonVariant::Solid];
        yield 'color from setCssClass()' => [null, null, 'btn btn-danger action-foo', '', ButtonColor::Danger, ButtonVariant::Light];
        yield 'the configured color wins over CSS classes' => [ButtonColor::Success, null, '', 'btn-primary', ButtonColor::Success, ButtonVariant::Light];
        yield 'color from an outline CSS class' => [null, null, '', 'btn btn-outline-danger', ButtonColor::Danger, ButtonVariant::Light];
        yield 'similar CSS classes are ignored' => [null, null, '', 'btn-primary-custom text-primary', ButtonColor::Default, ButtonVariant::Outline];
    }

    public function testAStyleCanBeSetWithTheVariant(): void
    {
        $actionDto = new ActionDto();
        $actionDto->setColor(ButtonColor::Danger);
        $actionDto->setVariant(ButtonVariant::Outline);

        $this->assertSame(ButtonColor::Danger, $actionDto->getColor());
        $this->assertSame(ButtonVariant::Outline, $actionDto->getButtonVariant());
    }

    #[IgnoreDeprecations]
    public function testPassingAColorToSetVariantIsDeprecated(): void
    {
        $this->expectUserDeprecationMessageMatches('/Passing a color or null to ".*setVariant\(\)" is deprecated/');

        $actionDto = new ActionDto();
        $actionDto->setVariant(ButtonVariant::Danger);

        $this->assertSame(ButtonColor::Danger, $actionDto->getColor());
    }

    #[IgnoreDeprecations]
    public function testGetVariantIsDeprecatedAndKeepsReturningTheColorCases(): void
    {
        $this->expectUserDeprecationMessageMatches('/".*getVariant\(\)" method is deprecated/');

        $actionDto = new ActionDto();
        $actionDto->setColor(ButtonColor::Danger);

        $this->assertSame(ButtonVariant::Danger, $actionDto->getVariant());
    }

    public function testTheConfiguredColorIgnoresCssClasses(): void
    {
        $actionDto = new ActionDto();
        $actionDto->setAddedCssClass('btn btn-danger');

        $this->assertSame(ButtonColor::Danger, $actionDto->getColor());
        $this->assertSame(ButtonColor::Default, $actionDto->getConfiguredColor());
    }
}
