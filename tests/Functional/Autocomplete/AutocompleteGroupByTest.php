<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Autocomplete;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\ProjectDomain\DeveloperCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\ProjectDomain\ProjectCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\ProjectDomain\ProjectIssueWithAutocompleteCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\ProjectDomain\ProjectIssueWithGroupByCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\DeveloperFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\ProjectFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\ProjectIssueFactory;

class AutocompleteGroupByTest extends AbstractCrudTestCase
{
    protected function getControllerFqcn(): string
    {
        return ProjectIssueWithGroupByCrudController::class;
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

    /**
     * @return list<array<string, mixed>>
     */
    private function requestAutocompleteResults(string $targetControllerFqcn, string $originatingControllerFqcn, string $propertyName, string $query): array
    {
        $autocompleteUrl = $this->adminUrlGenerator
            ->setDashboard(DashboardController::class)
            ->setController($targetControllerFqcn)
            ->setAction('autocomplete')
            ->set('page', 1)
            ->set('query', $query)
            ->set('autocompleteContext', [
                'crudControllerFqcn' => $originatingControllerFqcn,
                'propertyName' => $propertyName,
                'originatingPage' => 'new',
            ])
            ->generateUrl();

        $this->client->request('GET', $autocompleteUrl);
        $this->assertResponseIsSuccessful();

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('results', $data);

        return $data['results'];
    }

    public function testGroupByCallableAddsTheGroupToEachResult(): void
    {
        ProjectFactory::createOne(['name' => 'Groupby Internal Tooling', 'internal' => true]);
        ProjectFactory::createOne(['name' => 'Groupby Public Website', 'internal' => false]);

        $results = $this->requestAutocompleteResults(
            ProjectCrudController::class,
            ProjectIssueWithGroupByCrudController::class,
            'project',
            'Groupby'
        );

        $this->assertCount(2, $results);

        $resultsByLabel = array_column($results, null, 'entityAsString');
        $this->assertSame('Internal', $resultsByLabel['Groupby Internal Tooling']['entityGroup']);
        // a null group leaves the result ungrouped: the key must not even be present
        $this->assertArrayNotHasKey('entityGroup', $resultsByLabel['Groupby Public Website']);
    }

    public function testGroupByPropertyPathAddsTheGroupToEachResult(): void
    {
        DeveloperFactory::createOne(['name' => 'Groupby Grace Hopper']);

        $results = $this->requestAutocompleteResults(
            DeveloperCrudController::class,
            ProjectIssueWithGroupByCrudController::class,
            'assignedDeveloper',
            'Groupby Grace'
        );

        $this->assertCount(1, $results);
        $this->assertSame('Groupby Grace Hopper', $results[0]['entityGroup']);
    }

    public function testResultsHaveNoGroupWhenGroupByIsNotDefined(): void
    {
        ProjectFactory::createOne(['name' => 'Groupby Control Project', 'internal' => true]);

        $results = $this->requestAutocompleteResults(
            ProjectCrudController::class,
            ProjectIssueWithAutocompleteCrudController::class,
            'project',
            'Groupby Control'
        );

        $this->assertCount(1, $results);
        $this->assertArrayNotHasKey('entityGroup', $results[0]);
    }

    public function testSelectedChoicesAreRenderedInsideOptgroupsInTheForm(): void
    {
        $internalProject = ProjectFactory::createOne(['name' => 'Groupby Form Project', 'internal' => true]);
        $issue = ProjectIssueFactory::createOne(['name' => 'Groupby Form Issue', 'project' => $internalProject]);

        $editUrl = $this->adminUrlGenerator
            ->setDashboard(DashboardController::class)
            ->setController(ProjectIssueWithGroupByCrudController::class)
            ->setAction('edit')
            ->setEntityId($issue->getId())
            ->generateUrl();

        $crawler = $this->client->request('GET', $editUrl);
        $this->assertResponseIsSuccessful();

        $selectedOption = $crawler->filter('optgroup[label="Internal"] option[selected]');
        $this->assertCount(1, $selectedOption);
        $this->assertSame('Groupby Form Project', $selectedOption->text());
    }
}
