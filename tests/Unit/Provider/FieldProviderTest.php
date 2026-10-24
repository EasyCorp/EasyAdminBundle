<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Provider;

use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Context\AdminContextInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Provider\AdminContextProviderInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Provider\FieldProvider;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\DoctrineTypesTestEntity;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class FieldProviderTest extends KernelTestCase
{
    private FieldProvider $fieldProvider;

    protected function setUp(): void
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityDto = new EntityDto(DoctrineTypesTestEntity::class, $entityManager->getClassMetadata(DoctrineTypesTestEntity::class));

        $adminContext = $this->createStub(AdminContextInterface::class);
        $adminContext->method('getEntity')->willReturn($entityDto);

        $adminContextProvider = $this->createStub(AdminContextProviderInterface::class);
        $adminContextProvider->method('getContext')->willReturn($adminContext);

        $this->fieldProvider = new FieldProvider($adminContextProvider);
    }

    #[DataProvider('getDefaultFields')]
    public function testGetDefaultFields(string $pageName, array $expectedDefaultFields): void
    {
        $this->assertEquals(
            $this->removeUniqueIds($expectedDefaultFields),
            $this->removeUniqueIds($this->fieldProvider->getDefaultFields($pageName)),
        );
    }

    public static function getDefaultFields(): \Generator
    {
        yield [
            'not existing dummy page type',
            [
                Field::new('id'),
                Field::new('stringColumn'),
                Field::new('simpleArrayColumn'),
                Field::new('jsonColumn'),
                Field::new('booleanColumn'),
                Field::new('textColumn'),
                Field::new('dateMutableColumn'),
                Field::new('dateImmutableColumn'),
                Field::new('dateTimeMutableColumn'),
                Field::new('dateTimeImmutableColumn'),
                Field::new('dateTimeTzMutableColumn'),
                Field::new('dateTimeTzImmutableColumn'),
                Field::new('integerColumn'),
                Field::new('smallintColumn'),
                Field::new('decimalColumn'),
                Field::new('floatColumn'),
                Field::new('timeMutableColumn'),
                Field::new('timeImmutableColumn'),
                Field::new('address.street'),
                Field::new('address.city'),
            ],
        ];
        yield [
            Crud::PAGE_DETAIL,
            [
                Field::new('id'),
                Field::new('stringColumn'),
                Field::new('simpleArrayColumn'),
                Field::new('booleanColumn'),
                Field::new('textColumn'),
                Field::new('dateMutableColumn'),
                Field::new('dateImmutableColumn'),
                Field::new('dateTimeMutableColumn'),
                Field::new('dateTimeImmutableColumn'),
                Field::new('dateTimeTzMutableColumn'),
                Field::new('dateTimeTzImmutableColumn'),
                Field::new('integerColumn'),
                Field::new('smallintColumn'),
                Field::new('decimalColumn'),
                Field::new('floatColumn'),
                Field::new('timeMutableColumn'),
                Field::new('timeImmutableColumn'),
                Field::new('address.street'),
                Field::new('address.city'),
            ],
        ];
        yield [
            Crud::PAGE_EDIT,
            [
                Field::new('stringColumn'),
                Field::new('simpleArrayColumn'),
                Field::new('booleanColumn'),
                Field::new('textColumn'),
                Field::new('dateMutableColumn'),
                Field::new('dateImmutableColumn'),
                Field::new('dateTimeMutableColumn'),
                Field::new('dateTimeImmutableColumn'),
                Field::new('dateTimeTzMutableColumn'),
                Field::new('dateTimeTzImmutableColumn'),
                Field::new('integerColumn'),
                Field::new('smallintColumn'),
                Field::new('decimalColumn'),
                Field::new('floatColumn'),
                Field::new('timeMutableColumn'),
                Field::new('timeImmutableColumn'),
                Field::new('address.street'),
                Field::new('address.city'),
            ],
        ];
        yield [
            Crud::PAGE_INDEX,
            [
                Field::new('id'),
                Field::new('stringColumn'),
                Field::new('simpleArrayColumn'),
                Field::new('booleanColumn'),
                Field::new('dateMutableColumn'),
                Field::new('dateImmutableColumn'),
                Field::new('dateTimeMutableColumn'),
            ],
        ];
        yield [
            Crud::PAGE_NEW,
            [
                Field::new('stringColumn'),
                Field::new('simpleArrayColumn'),
                Field::new('booleanColumn'),
                Field::new('textColumn'),
                Field::new('dateMutableColumn'),
                Field::new('dateImmutableColumn'),
                Field::new('dateTimeMutableColumn'),
                Field::new('dateTimeImmutableColumn'),
                Field::new('dateTimeTzMutableColumn'),
                Field::new('dateTimeTzImmutableColumn'),
                Field::new('integerColumn'),
                Field::new('smallintColumn'),
                Field::new('decimalColumn'),
                Field::new('floatColumn'),
                Field::new('timeMutableColumn'),
                Field::new('timeImmutableColumn'),
                Field::new('address.street'),
                Field::new('address.city'),
            ],
        ];
    }

    private function removeUniqueIds(array $fields): array
    {
        array_map(static fn (Field $field) => $field->getAsDto()->setUniqueId('-'), $fields);

        return $fields;
    }
}
