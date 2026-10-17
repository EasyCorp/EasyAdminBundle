<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Customization\Crud\Search;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\Crud\Search\SearchModeTestCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Factory\DemoEntityFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Kernel;

/**
 * Tests for Crud::setSearchMode() configuration method.
 */
class SearchModeTest extends AbstractCrudTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function getControllerFqcn(): string
    {
        return SearchModeTestCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return \EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\DashboardController::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();
    }

    public function testSearchModeConfiguration(): void
    {
        $matchingAllTerms = DemoEntityFactory::createOne(['name' => 'Demo Item']);
        DemoEntityFactory::createOne(['name' => 'Demo Product']);
        DemoEntityFactory::createOne(['name' => 'Other Item']);

        $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // verify data is displayed before search
        $this->assertIndexPageEntityCount(3);
        static::assertIndexFullEntityCount(3);

        // ALL_TERMS mode only returns the rows that match every search term
        $this->client->request('GET', $this->generateIndexUrl('Demo Item'));

        static::assertResponseIsSuccessful();
        $this->assertIndexPageEntityCount(1);
        static::assertSelectorExists($this->getIndexEntityRowSelector($matchingAllTerms->getId()));
    }
}
