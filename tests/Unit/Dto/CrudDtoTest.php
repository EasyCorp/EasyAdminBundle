<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Dto;

use EasyCorp\Bundle\EasyAdminBundle\Dto\CrudDto;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\TranslatableMessage;

class CrudDtoTest extends TestCase
{
    public function testAskConfirmationOnBatchActionsDefaultValue(): void
    {
        $crudDto = new CrudDto();

        $this->assertTrue($crudDto->askConfirmationOnBatchActions());
    }

    public function testAskConfirmationOnBatchActionsWithBooleanFalse(): void
    {
        $crudDto = new CrudDto();
        $crudDto->setAskConfirmationOnBatchActions(false);

        $this->assertFalse($crudDto->askConfirmationOnBatchActions());
    }

    public function testAskConfirmationOnBatchActionsWithBooleanTrue(): void
    {
        $crudDto = new CrudDto();
        $crudDto->setAskConfirmationOnBatchActions(false);
        $crudDto->setAskConfirmationOnBatchActions(true);

        $this->assertTrue($crudDto->askConfirmationOnBatchActions());
    }

    public function testAskConfirmationOnBatchActionsWithCustomMessage(): void
    {
        $crudDto = new CrudDto();
        $customMessage = 'Are you sure you want to apply %action_name% to %num_items% items?';
        $crudDto->setAskConfirmationOnBatchActions($customMessage);

        $this->assertSame($customMessage, $crudDto->askConfirmationOnBatchActions());
    }

    public function testAskConfirmationOnBatchActionsWithTranslatableMessage(): void
    {
        $crudDto = new CrudDto();
        $translatableMessage = new TranslatableMessage('batch.confirm.message');
        $crudDto->setAskConfirmationOnBatchActions($translatableMessage);

        $this->assertSame($translatableMessage, $crudDto->askConfirmationOnBatchActions());
    }

    /**
     * @dataProvider provideLabels
     *
     * @param string|closure|null $setLabel
     */
    public function testGetEntityLabelInSingular($setLabel, ?string $expectedGetLabel): void
    {
        $crudDto = new CrudDto();

        if (null !== $setLabel) {
            $crudDto->setEntityLabelInSingular($setLabel);
            $crudDto->setEntityLabelInPlural($setLabel);
        }

        $entityInstance = new class {
            public function getPrimaryKeyValue(): string
            {
                return '42';
            }
        };
        $this->assertSame($expectedGetLabel, $crudDto->getEntityLabelInSingular($entityInstance));
        $this->assertSame($expectedGetLabel, $crudDto->getEntityLabelInPlural($entityInstance));
    }

    public static function provideLabels(): \Generator
    {
        yield [null, null];
        yield ['', ''];
        yield ['foo', 'foo'];
        yield ['Foo Bar', 'Foo Bar'];
        // see https://github.com/EasyCorp/EasyAdminBundle/issues/4176
        yield ['link', 'link'];
        yield [static function () { return null; }, null];
        yield [static function () { return ''; }, ''];
        yield [static function () { return 'foo'; }, 'foo'];
        yield [static function () { return 'Foo Bar'; }, 'Foo Bar'];
        yield [static function () { return 'link'; }, 'link'];
        yield [static function ($entityInstance) { return 'Entity #'.$entityInstance->getPrimaryKeyValue(); }, 'Entity #42'];
    }
}
