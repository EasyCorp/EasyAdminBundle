<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Provider;

use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Context\AdminContextInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Provider\AdminContextProviderInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Provider\FieldProvider;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\ProjectDomain\Project;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class FieldProviderTest extends KernelTestCase
{
    private FieldProvider $fieldProvider;

    protected function setUp(): void
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $projectDto = new EntityDto(Project::class, $entityManager->getClassMetadata(Project::class));

        $adminContext = $this->createStub(AdminContextInterface::class);
        $adminContext->method('getEntity')->willReturn($projectDto);

        $adminContextProvider = $this->createStub(AdminContextProviderInterface::class);
        $adminContextProvider->method('getContext')->willReturn($adminContext);

        $this->fieldProvider = new FieldProvider($adminContextProvider);
    }

    /**
     * @dataProvider getDefaultFields
     */
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
                Field::new('name'),
                Field::new('statesSimpleArray'),
                Field::new('rolesJson'),
                Field::new('startDate'),
                Field::new('internal'),
                Field::new('description'),
                Field::new('startDateMutable'),
                Field::new('startDateImmutable'),
                Field::new('startDateTimeMutable'),
                Field::new('startDateTimeImmutable'),
                Field::new('startDateTimeTzMutable'),
                Field::new('startDateTimeTzImmutable'),
                Field::new('countInteger'),
                Field::new('countSmallint'),
                Field::new('priceDecimal'),
                Field::new('priceFloat'),
                Field::new('startTimeMutable'),
                Field::new('startTimeImmutable'),
                Field::new('price.amount'),
                Field::new('price.currency'),
            ],
        ];
        yield [
            Crud::PAGE_DETAIL,
            [
                Field::new('id'),
                Field::new('name'),
                Field::new('statesSimpleArray'),
                Field::new('startDate'),
                Field::new('internal'),
                Field::new('description'),
                Field::new('startDateMutable'),
                Field::new('startDateImmutable'),
                Field::new('startDateTimeMutable'),
                Field::new('startDateTimeImmutable'),
                Field::new('startDateTimeTzMutable'),
                Field::new('startDateTimeTzImmutable'),
                Field::new('countInteger'),
                Field::new('countSmallint'),
                Field::new('priceDecimal'),
                Field::new('priceFloat'),
                Field::new('startTimeMutable'),
                Field::new('startTimeImmutable'),
                Field::new('price.amount'),
                Field::new('price.currency'),
            ],
        ];
        yield [
            Crud::PAGE_EDIT,
            [
                Field::new('name'),
                Field::new('statesSimpleArray'),
                Field::new('startDate'),
                Field::new('internal'),
                Field::new('description'),
                Field::new('startDateMutable'),
                Field::new('startDateImmutable'),
                Field::new('startDateTimeMutable'),
                Field::new('startDateTimeImmutable'),
                Field::new('startDateTimeTzMutable'),
                Field::new('startDateTimeTzImmutable'),
                Field::new('countInteger'),
                Field::new('countSmallint'),
                Field::new('priceDecimal'),
                Field::new('priceFloat'),
                Field::new('startTimeMutable'),
                Field::new('startTimeImmutable'),
                Field::new('price.amount'),
                Field::new('price.currency'),
            ],
        ];
        yield [
            Crud::PAGE_INDEX,
            [
                Field::new('id'),
                Field::new('name'),
                Field::new('statesSimpleArray'),
                Field::new('startDate'),
                Field::new('internal'),
                Field::new('startDateMutable'),
                Field::new('startDateImmutable'),
            ],
        ];
        yield [
            Crud::PAGE_NEW,
            [
                Field::new('name'),
                Field::new('statesSimpleArray'),
                Field::new('startDate'),
                Field::new('internal'),
                Field::new('description'),
                Field::new('startDateMutable'),
                Field::new('startDateImmutable'),
                Field::new('startDateTimeMutable'),
                Field::new('startDateTimeImmutable'),
                Field::new('startDateTimeTzMutable'),
                Field::new('startDateTimeTzImmutable'),
                Field::new('countInteger'),
                Field::new('countSmallint'),
                Field::new('priceDecimal'),
                Field::new('priceFloat'),
                Field::new('startTimeMutable'),
                Field::new('startTimeImmutable'),
                Field::new('price.amount'),
                Field::new('price.currency'),
            ],
        ];
    }

    private function removeUniqueIds(array $fields): array
    {
        array_map(static fn (Field $field) => $field->getAsDto()->setUniqueId('-'), $fields);

        return $fields;
    }
}
