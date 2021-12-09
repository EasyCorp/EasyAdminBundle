<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Field;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\CrudDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\I18nDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class AbstractFieldTest extends KernelTestCase
{
    protected $entityDto;
    protected $adminContext;
    protected $configurator;

    protected function setUp(): void
    {
        self::bootKernel();

        $entityDtoMock = $this->createMock(EntityDto::class);
        $entityDtoMock
            ->expects($this->any())
            ->method('getInstance')
            ->willReturn(new class() {});
        $this->entityDto = $entityDtoMock;

        $crudMock = $this->getMockBuilder(CrudDto::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCurrentPage', 'getDatePattern', 'getDateTimePattern', 'getTimePattern'])
            ->getMock();
        $crudMock->method('getCurrentPage')->willReturn(Crud::PAGE_INDEX);
        $crudMock->method('getDatePattern')->willReturn(DateTimeField::FORMAT_MEDIUM);
        $crudMock->method('getTimePattern')->willReturn(DateTimeField::FORMAT_MEDIUM);
        $crudMock->method('getDateTimePattern')->willReturn([DateTimeField::FORMAT_MEDIUM, DateTimeField::FORMAT_MEDIUM]);

        $i18nMock = $this->getMockBuilder(I18nDto::class)
            ->disableOriginalConstructor()
            ->setMethods(['getTranslationParameters', 'getTranslationDomain'])
            ->getMock();
        $i18nMock->method('getTranslationParameters')->willReturn([]);
        $i18nMock->method('getTranslationDomain')->willReturn('messages');

        $adminContextMock = $this->getMockBuilder(AdminContext::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCrud', 'getI18n'])
            ->getMock();
        $adminContextMock
            ->expects($this->any())
            ->method('getCrud')
            ->willReturn($crudMock);
        $adminContextMock
            ->expects($this->any())
            ->method('getI18n')
            ->willReturn($i18nMock);

        $this->adminContext = $adminContextMock;
    }

    protected function configure(FieldInterface $field): FieldDto
    {
        $fieldDto = $field->getAsDto();
        $this->configurator->configure($fieldDto, $this->entityDto, $this->adminContext);

        return $fieldDto;
    }
}
