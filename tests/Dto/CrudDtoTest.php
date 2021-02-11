<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Dto;

use EasyCorp\Bundle\EasyAdminBundle\Dto\CrudDto;
use PHPUnit\Framework\TestCase;

class CrudDtoTest extends TestCase
{
    /**
     * @dataProvider provideLabels
     *
     * @param string|closure|null $setLabel
     */
    public function testGetEntityLabelInSingular($setLabel, ?string $expectedGetLabel)
    {
        $crudDto = new CrudDto();

        if (null !== $setLabel) {
            $crudDto->setEntityLabelInSingular($setLabel);
            $crudDto->setEntityLabelInPlural($setLabel);
        }

        $entityInstance = new class() {
            public function getPrimaryKeyValue()
            {
                return '42';
            }
        };
        $this->assertSame($expectedGetLabel, $crudDto->getEntityLabelInSingular($entityInstance));
        $this->assertSame($expectedGetLabel, $crudDto->getEntityLabelInPlural($entityInstance));
    }

    public function provideLabels()
    {
        yield [null, null];
        yield ['', ''];
        yield ['foo', 'foo'];
        yield ['Foo Bar', 'Foo Bar'];
        // see https://github.com/EasyCorp/EasyAdminBundle/issues/4176
        yield ['link', 'link'];
        yield [fn () => null, null];
        yield [fn () => '', ''];
        yield [fn () => 'foo', 'foo'];
        yield [fn () => 'Foo Bar', 'Foo Bar'];
        yield [fn () => 'link', 'link'];
        yield [fn ($entityInstance) => 'Entity #'.$entityInstance->getPrimaryKeyValue(), 'Entity #42'];
    }
}
