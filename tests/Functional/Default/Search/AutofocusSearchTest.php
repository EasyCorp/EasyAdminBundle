<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Search;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Search\AutofocusDisabledSearchController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Search\AutofocusEnabledSearchController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Search\DefaultCrudSearchController;

/**
 * Tests for the search autofocus feature.
 */
class AutofocusSearchTest extends AbstractCrudTestCase
{
    protected function getControllerFqcn(): string
    {
        return AutofocusEnabledSearchController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();
    }

    public function testSearchInputHasAutofocusWhenEnabled(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        $searchInput = $crawler->filter('form.form-action-search input[name="query"]');
        $this->assertCount(1, $searchInput, 'Search input should exist');
        $this->assertSame('autofocus', $searchInput->attr('autofocus'), 'Search input should have autofocus attribute when setAutofocusSearch(true) is configured');
    }

    public function testSearchInputDoesNotHaveAutofocusWhenDisabled(): void
    {
        $crawler = $this->client->request(
            'GET',
            $this->generateIndexUrl(null, null, AutofocusDisabledSearchController::class)
        );

        $searchInput = $crawler->filter('form.form-action-search input[name="query"]');
        $this->assertCount(1, $searchInput, 'Search input should exist');
        $this->assertNull($searchInput->attr('autofocus'), 'Search input should NOT have autofocus attribute when setAutofocusSearch(false) is configured');
    }

    public function testSearchInputDoesNotHaveAutofocusByDefault(): void
    {
        // defaultCrudSearchController does not call setAutofocusSearch(), so the default behavior applies
        $crawler = $this->client->request(
            'GET',
            $this->generateIndexUrl(null, null, DefaultCrudSearchController::class)
        );

        $searchInput = $crawler->filter('form.form-action-search input[name="query"]');
        $this->assertCount(1, $searchInput, 'Search input should exist');
        $this->assertNull($searchInput->attr('autofocus'), 'Search input should NOT have autofocus attribute by default (when setAutofocusSearch is not called)');
    }
}
