<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Dto;

use EasyCorp\Bundle\EasyAdminBundle\Dto\ActionDto;
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
}
