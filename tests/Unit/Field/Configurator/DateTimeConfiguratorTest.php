<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Field\Configurator;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\FieldMapping;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\CrudContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Intl\IntlFormatterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\CrudDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\DateTimeConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DateTimeConfiguratorTest extends TestCase
{
    /**
     * Without the 'input' option, the form gives a \DateTime object
     * to properties that only accept \DateTimeImmutable objects.
     */
    #[DataProvider('doctrineTypeProvider')]
    public function testInputFormOptionDependsOnDoctrineType(string $doctrineType, ?string $expectedInput): void
    {
        // Doctrine ORM 2.x uses arrays, Doctrine ORM 3.x uses FieldMapping objects
        $fieldMapping = class_exists(FieldMapping::class)
            ? new FieldMapping($doctrineType, 'createdAt', 'created_at')
            : ['fieldName' => 'createdAt', 'type' => $doctrineType, 'columnName' => 'created_at'];

        $classMetadata = $this->createStub(ClassMetadata::class);
        $classMetadata->method('getFieldMapping')->willReturn($fieldMapping);
        $classMetadata->fieldMappings = ['createdAt' => $fieldMapping];

        $fieldDto = DateTimeField::new('createdAt')->getAsDto();
        $fieldDto->setFieldFqcn(DateTimeField::class);

        $configurator = new DateTimeConfigurator($this->createStub(IntlFormatterInterface::class));
        $configurator->configure($fieldDto, new EntityDto(\stdClass::class, $classMetadata), AdminContext::forTesting(crudContext: CrudContext::forTesting(new CrudDto())));

        $this->assertSame($expectedInput, $fieldDto->getFormTypeOption('input'));
    }

    public static function doctrineTypeProvider(): \Generator
    {
        yield 'datetime' => [Types::DATETIME_MUTABLE, null];
        yield 'datetime_immutable' => [Types::DATETIME_IMMUTABLE, 'datetime_immutable'];
        yield 'datetimetz_immutable' => [Types::DATETIMETZ_IMMUTABLE, 'datetime_immutable'];
        // these types are string literals because their constants need Doctrine DBAL 4.5
        yield 'datetime_utc' => ['datetime_utc', null];
        yield 'datetime_utc_immutable' => ['datetime_utc_immutable', 'datetime_immutable'];
    }
}
