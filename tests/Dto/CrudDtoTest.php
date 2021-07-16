<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Dto;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
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

    /**
     * @dataProvider provideTitles
     *
     * @param string|closure|null $setTitle
     * @param string|closure|null $setSubTitle
     */
    public function testCustomPageTitle(string $pageName, $setTitle, $setSubTitle, ?string $expectedGetTitle, ?string $exptectedGetSubTitle)
    {
        $crudDto = new CrudDto();

        if (null !== $setTitle) {
            $crudDto->setCustomPageTitle($pageName, $setTitle);
        }

        if (null !== $setSubTitle) {
            $crudDto->setCustomPageSubTitle($pageName, $setSubTitle);
        }

        $entityInstance = new class() {
            public function getPrimaryKeyValue()
            {
                return '42';
            }
        };
        $this->assertSame($expectedGetTitle, $crudDto->getCustomPageTitle($pageName, $entityInstance));
        $this->assertSame($exptectedGetSubTitle, $crudDto->getCustomPageSubTitle($pageName, $entityInstance));
    }

    public function provideTitles()
    {
        foreach ([Crud::PAGE_DETAIL, Crud::PAGE_EDIT, Crud::PAGE_INDEX, Crud::PAGE_NEW] as $pageName) {
            yield [$pageName, null, null, null, null];
            yield [$pageName, 'foo', 'bar', 'foo', 'bar'];
            yield [$pageName, 'Foo Bar', 'Foo2 Bar2', 'Foo Bar', 'Foo2 Bar2'];
            yield [$pageName, 'link', 'link', 'link', 'link'];
            yield [$pageName, '', '', '', ''];
            yield [$pageName, fn () => 'foo', fn () => 'bar', 'foo', 'bar'];
            yield [$pageName, fn () => 'Foo Bar', fn () => 'Foo2 Bar2', 'Foo Bar', 'Foo2 Bar2'];
            yield [$pageName, fn ($entityInstance) => 'Foo Bar #'.$entityInstance->getPrimaryKeyValue(), fn ($entityInstance) => 'Foo2 Bar2 #'.$entityInstance->getPrimaryKeyValue(), 'Foo Bar #42', 'Foo2 Bar2 #42'];
        }
    }
}
