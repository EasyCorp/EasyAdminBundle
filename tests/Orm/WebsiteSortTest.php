<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Orm;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller\Sort\WebsiteCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Entity\Website;

class WebsiteSortTest extends AbstractCrudTestCase
{
    private $repository;

    protected function getControllerFqcn(): string
    {
        return WebsiteCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();
        $this->repository = $this->entityManager->getRepository(Website::class);
    }

    /**
     * @dataProvider sorting
     */
    public function testSorting(array $query, ?string $sortFunction, string $expectedSortIcon)
    {
        // Arrange
        $expectedAmountMapping = [];

        /**
         * @var Website $entity
         */
        foreach ($this->repository->findAll() as $entity) {
            $expectedAmountMapping[$entity->getName()] = $entity->getPages()->count();
        }

        if (null !== $sortFunction) {
            $sortFunction($expectedAmountMapping);
        }

        // Act
        $crawler = $this->client->request('GET', $this->generateIndexUrl().'&'.http_build_query($query));

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextSame('th.header-for-field-association > a', 'Pages');
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
            ['sort' => ['pages' => 'ASC']],
            'asort',
            'fa-arrow-up',
        ];

        yield [
            ['sort' => ['pages' => 'DESC']],
            'arsort',
            'fa-arrow-down',
        ];
    }
}
