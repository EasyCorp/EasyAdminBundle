<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Orm;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller\Sort\CustomerCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Entity\Customer;

class CustomerSortTest extends AbstractCrudTestCase
{
    private $repository;

    protected function getControllerFqcn(): string
    {
        return CustomerCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();
        $this->repository = $this->entityManager->getRepository(Customer::class);
    }

    /**
     * @dataProvider sorting
     */
    public function testSorting(array $query, ?string $sortFunction, string $expectedSortIcon)
    {
        // Arrange
        $expectedAmountMapping = [];

        /**
         * @var Customer $entity
         */
        foreach ($this->repository->findAll() as $entity) {
            $expectedAmountMapping[$entity->getName()] = $entity->getBills()->count();
        }

        if (null !== $sortFunction) {
            $sortFunction($expectedAmountMapping);
        }

        // Act
        $crawler = $this->client->request('GET', $this->generateIndexUrl().'&'.http_build_query($query));

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextSame('th.header-for-field-association > a', 'Bills');
        $this->assertSelectorExists('th.header-for-field-association i.'.$expectedSortIcon);

        $index = 1;

        foreach ($expectedAmountMapping as $expectedName => $expectedValue) {
            $expectedRow = $index++;

            $this->assertSelectorTextSame('tbody tr:nth-child('.$expectedRow.') td:nth-child(2)', $expectedName, sprintf('Expected "%s" in row %d', $expectedName, $expectedRow));
            $this->assertSelectorTextSame('tbody tr:nth-child('.$expectedRow.') td:nth-child(3)', $expectedValue, sprintf('Expected "%s" in row %d', $expectedValue, $expectedRow));
        }
    }

    public function sorting(): \Generator
    {
        yield [
            [],
            null,
            'fa-sort',
        ];

        yield [
            ['sort' => ['bills' => 'ASC']],
            'asort',
            'fa-arrow-up',
        ];

        yield [
            ['sort' => ['bills' => 'DESC']],
            'arsort',
            'fa-arrow-down',
        ];
    }
}
