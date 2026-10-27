<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Filters;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonType;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\FilterTestEntityFactory;

class AdminUrlGeneratorFilterTest extends FilterFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $values = [
            'Entity 1' => [100, true],
            'Entity 2' => [200, false],
            'Entity 3' => [300, true],
            'Entity 4' => [400, true],
        ];
        foreach ($values as $key => [$number, $boolean]) {
            $this->entities[$key] = FilterTestEntityFactory::createOne([
                'numericFilter' => $number,
                'booleanFilter' => $boolean,
            ]);
        }
    }

    public function testFilterWithComparison(): void
    {
        $this->client->request('GET', $this->getIndexUrlGenerator()->setFilter('numericFilter', 200, ComparisonType::NEQ)->generateUrl());

        $this->assertFilteredCount(3);
        $this->assertVisibleEntities(['Entity 1', 'Entity 3', 'Entity 4']);
    }

    public function testFilterWithoutComparison(): void
    {
        $this->client->request('GET', $this->getIndexUrlGenerator()->setFilter('booleanFilter', false)->generateUrl());

        $this->assertFilteredCount(1);
        $this->assertVisibleEntities(['Entity 2']);
    }

    public function testFilterWithBetweenComparison(): void
    {
        $this->client->request('GET', $this->getIndexUrlGenerator()->setFilter('numericFilter', 150, ComparisonType::BETWEEN, 350)->generateUrl());

        $this->assertFilteredCount(2);
        $this->assertVisibleEntities(['Entity 2', 'Entity 3']);
    }

    public function testSeveralFilters(): void
    {
        $url = $this->getIndexUrlGenerator()
            ->setFilter('numericFilter', 200, ComparisonType::GT)
            ->setFilter('booleanFilter', true)
            ->generateUrl();
        $this->client->request('GET', $url);

        $this->assertFilteredCount(2);
        $this->assertVisibleEntities(['Entity 3', 'Entity 4']);
    }

    public function testUnsetFilter(): void
    {
        $url = $this->getIndexUrlGenerator()
            ->setFilter('numericFilter', 200, ComparisonType::GT)
            ->setFilter('booleanFilter', false)
            ->unsetFilter('booleanFilter')
            ->generateUrl();
        $this->client->request('GET', $url);

        $this->assertFilteredCount(2);
        $this->assertVisibleEntities(['Entity 3', 'Entity 4']);
    }

    private function getIndexUrlGenerator(): AdminUrlGenerator
    {
        /** @var AdminUrlGenerator $adminUrlGenerator */
        $adminUrlGenerator = static::getContainer()->get(AdminUrlGenerator::class);
        $adminUrlGenerator
            ->setDashboard($this->getDashboardFqcn())
            ->setController($this->getControllerFqcn())
            ->setAction(Action::INDEX);

        return $adminUrlGenerator;
    }
}
