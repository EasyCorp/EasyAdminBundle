<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Filter\Configurator;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\FieldMapping;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\CrudContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\DashboardContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\I18nContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\RequestContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Orm\NestedAssociationResolverInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\ResolvedPropertyDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\Configurator\DateTimeConfigurator;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;

final class DateTimeConfiguratorTest extends TestCase
{
    use ExpectDeprecationTrait;

    /**
     * @dataProvider fieldProvider
     */
    public function testConfigure(string $propertyPath, string $resolvedPropertyName, string $doctrineType, string $formTypeOptionName, mixed $expectedValue): void
    {
        $rootEntityDto = new EntityDto('App\Entity\Post', $this->createStub(ClassMetadata::class));
        $resolvedEntityDto = $this->createEntityDto($resolvedPropertyName, $doctrineType);

        $associationResolver = $this->createMock(NestedAssociationResolverInterface::class);
        $associationResolver->expects(self::once())
            ->method('resolveNestedAssociations')
            ->with(null, $rootEntityDto, $propertyPath)
            ->willReturn(new ResolvedPropertyDto($resolvedEntityDto, null, $resolvedPropertyName));

        $filterDto = new FilterDto();
        $filterDto->setProperty($propertyPath);

        (new DateTimeConfigurator($associationResolver))->configure($filterDto, null, $rootEntityDto, $this->createAdminContext());

        self::assertSame($expectedValue, $filterDto->getFormTypeOption($formTypeOptionName));
    }

    public static function fieldProvider(): iterable
    {
        yield 'date field' => ['publishedOn', 'publishedOn', Types::DATE_MUTABLE, 'value_type', DateType::class];
        yield 'nested date field' => ['author.birthDate', 'birthDate', Types::DATE_MUTABLE, 'value_type', DateType::class];
        yield 'nested time field' => ['author.wakeUpTime', 'wakeUpTime', Types::TIME_MUTABLE, 'value_type', TimeType::class];
        yield 'nested immutable datetime field' => ['author.createdAt', 'createdAt', Types::DATETIME_IMMUTABLE, 'value_type_options.input', 'datetime_immutable'];
    }

    public function testConfigureIgnoresUnmappedProperties(): void
    {
        $associationResolver = $this->createStub(NestedAssociationResolverInterface::class);
        $associationResolver->method('resolveNestedAssociations')->willThrowException(new \InvalidArgumentException());

        $filterDto = new FilterDto();
        $filterDto->setProperty('customProperty');

        (new DateTimeConfigurator($associationResolver))->configure($filterDto, null, new EntityDto('App\Entity\Post', $this->createStub(ClassMetadata::class)), $this->createAdminContext());

        self::assertSame([], $filterDto->getFormTypeOptions());
    }

    /**
     * @group legacy
     */
    public function testConfigureWithoutAssociationResolverOnlySupportsRootProperties(): void
    {
        $this->expectDeprecation('Since easycorp/easyadmin-bundle 5.7.0: Not passing an instance of "EasyCorp\\Bundle\\EasyAdminBundle\\Contracts\\Orm\\NestedAssociationResolverInterface" as the first argument of "EasyCorp\\Bundle\\EasyAdminBundle\\Filter\\Configurator\\DateTimeConfigurator::__construct()" is deprecated and it will be required in EasyAdmin 6.0.');
        $configurator = new DateTimeConfigurator();
        $entityDto = $this->createEntityDto('publishedOn', Types::DATE_MUTABLE);

        $rootFilterDto = new FilterDto();
        $rootFilterDto->setProperty('publishedOn');
        $configurator->configure($rootFilterDto, null, $entityDto, $this->createAdminContext());

        $nestedFilterDto = new FilterDto();
        $nestedFilterDto->setProperty('author.publishedOn');
        $configurator->configure($nestedFilterDto, null, $entityDto, $this->createAdminContext());

        self::assertSame(DateType::class, $rootFilterDto->getFormTypeOption('value_type'));
        self::assertSame([], $nestedFilterDto->getFormTypeOptions());
    }

    private function createEntityDto(string $propertyName, string $doctrineType): EntityDto
    {
        // Doctrine ORM 2.x uses arrays, Doctrine ORM 3.x uses FieldMapping objects
        $fieldMapping = class_exists(FieldMapping::class)
            ? new FieldMapping($doctrineType, $propertyName, $propertyName)
            : ['fieldName' => $propertyName, 'type' => $doctrineType, 'columnName' => $propertyName];

        $classMetadata = $this->createStub(ClassMetadata::class);
        $classMetadata->method('getFieldMapping')->willReturnMap([[$propertyName, $fieldMapping]]);
        $classMetadata->fieldMappings = [$propertyName => $fieldMapping];

        return new EntityDto('App\Entity\User', $classMetadata);
    }

    private function createAdminContext(): AdminContext
    {
        return new AdminContext(
            RequestContext::forTesting(),
            CrudContext::forTesting(),
            DashboardContext::forTesting(),
            I18nContext::forTesting(),
        );
    }
}
