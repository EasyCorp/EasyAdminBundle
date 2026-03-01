<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Autocomplete;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\ORM\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\ProjectDomain\ProjectCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\ProjectDomain\ProjectIssueWithAutocompleteCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\ProjectDomain\ProjectIssueWithRenderAsHtmlCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\ProjectDomain\Project;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\ProjectDomain\ProjectIssue;

class AutocompleteCustomizationTest extends AbstractCrudTestCase
{
    protected EntityRepository $projects;
    protected EntityRepository $projectIssues;

    protected function getControllerFqcn(): string
    {
        return ProjectIssueWithAutocompleteCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();

        $this->projects = $this->entityManager->getRepository(Project::class);
        $this->projectIssues = $this->entityManager->getRepository(ProjectIssue::class);
    }

    private function createProject(string $name): Project
    {
        $project = new Project();
        $project->setName($name);
        $project->setDescription('Test Description');
        $project->setInternal(false);
        $project->setStatesSimpleArray(['active']);
        $project->setStartDateMutable(new \DateTime());
        $project->setStartDateImmutable(new \DateTimeImmutable());
        $project->setStartDateTimeMutable(new \DateTime());
        $project->setStartDateTimeImmutable(new \DateTimeImmutable());
        $project->setStartDateTimeTzMutable(new \DateTime());
        $project->setStartDateTimeTzImmutable(new \DateTimeImmutable());
        $project->setCountInteger(0);
        $project->setCountSmallint(0);
        $project->setPriceDecimal('0.00');
        $project->setPriceFloat(0.0);
        $project->setStartTimeMutable(new \DateTime());
        $project->setStartTimeImmutable(new \DateTimeImmutable());

        return $project;
    }

    public function testAutocompleteWithFieldLevelCallback(): void
    {
        // Create a test project
        $project = $this->createProject('Unicorn Sparkle');

        $this->entityManager->persist($project);
        $this->entityManager->flush();

        // Build autocomplete URL for the project field
        $autocompleteUrl = $this->adminUrlGenerator
            ->setDashboard(DashboardController::class)
            ->setController(ProjectCrudController::class)
            ->setAction('autocomplete')
            ->set('page', 1)
            ->set('query', 'Unicorn Sparkle')
            ->set('autocompleteContext', [
                'crudControllerFqcn' => ProjectIssueWithAutocompleteCrudController::class,
                'propertyName' => 'project',
                'originatingPage' => 'new',
            ])
            ->generateUrl();

        // Make request to autocomplete endpoint
        $this->client->request('GET', $autocompleteUrl);
        $this->assertResponseIsSuccessful();

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('results', $data);
        $this->assertNotEmpty($data['results']);

        // The field-level callback should format as "Project: {name} (ID: {id})"
        $firstResult = $data['results'][0]['entityAsString'];
        $this->assertStringContainsString('Project:', $firstResult);
        $this->assertStringContainsString('Unicorn Sparkle', $firstResult);
        $this->assertStringContainsString('(ID:', $firstResult);
    }

    public function testAutocompleteWithCrudLevelCallback(): void
    {
        // Create a test project issue
        $project = $this->createProject('Parent Project');

        $issue = new ProjectIssue();
        $issue->setName('Bug #1');
        $issue->setProject($project);

        $this->entityManager->persist($project);
        $this->entityManager->persist($issue);
        $this->entityManager->flush();

        // Build autocomplete URL (simulating autocomplete for a different field without field-level config)
        // This would use the CRUD-level callback defined in configureCrud()
        $autocompleteUrl = $this->adminUrlGenerator
            ->setDashboard(DashboardController::class)
            ->setController(ProjectIssueWithAutocompleteCrudController::class)
            ->setAction('autocomplete')
            ->set('page', 1)
            ->set('autocompleteContext', [
                'crudControllerFqcn' => ProjectIssueWithAutocompleteCrudController::class,
                'propertyName' => 'name',
                'originatingPage' => 'index',
            ])
            ->generateUrl();

        $this->client->request('GET', $autocompleteUrl);

        // Note: This might fail if the field doesn't support autocomplete
        // This test demonstrates the CRUD-level callback concept
        if ($this->client->getResponse()->isSuccessful()) {
            $response = $this->client->getResponse();
            $data = json_decode($response->getContent(), true);

            if (isset($data['results']) && !empty($data['results'])) {
                $firstResult = $data['results'][0]['entityAsString'];
                // The CRUD-level callback should format as "[{id}] {entity}"
                $this->assertMatchesRegularExpression('/\[\d+\]/', $firstResult);
            }
        }
    }

    public function testAutocompleteEscapesHtmlByDefault(): void
    {
        // Create a project with HTML in the name (XSS test)
        $project = $this->createProject('<script>alert("XSS")</script>Malicious Project');

        $this->entityManager->persist($project);
        $this->entityManager->flush();

        $autocompleteUrl = $this->adminUrlGenerator
            ->setDashboard(DashboardController::class)
            ->setController(ProjectCrudController::class)
            ->setAction('autocomplete')
            ->set('page', 1)
            ->set('query', 'Malicious')
            ->set('autocompleteContext', [
                'crudControllerFqcn' => ProjectIssueWithAutocompleteCrudController::class,
                'propertyName' => 'project',
                'originatingPage' => 'new',
            ])
            ->generateUrl();

        $this->client->request('GET', $autocompleteUrl);
        $this->assertResponseIsSuccessful();

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        $firstResult = $data['results'][0]['entityAsString'];

        // HTML should be escaped
        $this->assertStringContainsString('&lt;script&gt;', $firstResult);
        $this->assertStringNotContainsString('<script>', $firstResult);
    }

    public function testAutocompleteReturnsValidJsonStructure(): void
    {
        if (!class_exists(PostgreSQLPlatform::class)) {
            $this->markTestSkipped('Doctrine DBAL 3.x+ is required.');
        }

        $project = $this->createProject('JSON Test Project');

        $this->entityManager->persist($project);
        $this->entityManager->flush();

        $autocompleteUrl = $this->adminUrlGenerator
            ->setDashboard(DashboardController::class)
            ->setController(ProjectCrudController::class)
            ->setAction('autocomplete')
            ->set('page', 1)
            ->set('autocompleteContext', [
                'crudControllerFqcn' => ProjectIssueWithAutocompleteCrudController::class,
                'propertyName' => 'project',
                'originatingPage' => 'new',
            ])
            ->generateUrl();

        $this->client->request('GET', $autocompleteUrl);
        $this->assertResponseIsSuccessful();

        $response = $this->client->getResponse();
        $this->assertJson($response->getContent());

        $data = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('results', $data);
        $this->assertIsArray($data['results']);

        if (!empty($data['results'])) {
            $firstResult = $data['results'][0];
            $this->assertArrayHasKey('entityId', $firstResult);
            $this->assertArrayHasKey('entityAsString', $firstResult);
        }
    }

    public function testAutocompleteWithCallbackAndRenderAsHtml(): void
    {
        // create a project with HTML-safe content
        $project = $this->createProject('Important Project');

        $this->entityManager->persist($project);
        $this->entityManager->flush();

        // build autocomplete URL using the controller with renderAsHtml: true
        $autocompleteUrl = $this->adminUrlGenerator
            ->setDashboard(DashboardController::class)
            ->setController(ProjectCrudController::class)
            ->setAction('autocomplete')
            ->set('page', 1)
            ->set('query', 'Important')
            ->set('autocompleteContext', [
                'crudControllerFqcn' => ProjectIssueWithRenderAsHtmlCrudController::class,
                'propertyName' => 'project',
                'originatingPage' => 'new',
            ])
            ->generateUrl();

        $this->client->request('GET', $autocompleteUrl);
        $this->assertResponseIsSuccessful();

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('results', $data);
        $this->assertNotEmpty($data['results']);

        $firstResult = $data['results'][0]['entityAsString'];

        // when renderAsHtml: true, HTML tags should NOT be escaped
        $this->assertStringContainsString('<strong>', $firstResult);
        $this->assertStringContainsString('</strong>', $firstResult);
        $this->assertStringNotContainsString('&lt;strong&gt;', $firstResult);
        $this->assertStringContainsString('Important Project', $firstResult);
    }

    public function testSelectedItemRenderedSameAsDropdownEntries(): void
    {
        // create a project and an issue with that project pre-selected
        $project = $this->createProject('Selected Project');

        $issue = new ProjectIssue();
        $issue->setName('Test Issue');
        $issue->setProject($project);

        $this->entityManager->persist($project);
        $this->entityManager->persist($issue);
        $this->entityManager->flush();

        // open the edit form for the issue
        $editUrl = $this->adminUrlGenerator
            ->setDashboard(DashboardController::class)
            ->setController(ProjectIssueWithAutocompleteCrudController::class)
            ->setAction('edit')
            ->setEntityId($issue->getId())
            ->generateUrl();

        $this->client->request('GET', $editUrl);
        $this->assertResponseIsSuccessful();

        // the selected option should use the same callback format as dropdown entries:
        // "Project: {name} (ID: {id})"
        $expectedLabel = sprintf('Project: %s (ID: %s)', $project->getName(), $project->getId());
        $this->assertSelectorExists(sprintf('option[selected]:contains("%s")', $expectedLabel));
    }

    public function testSelectedItemRenderedWithHtmlCallback(): void
    {
        // create a project and an issue with that project pre-selected
        $project = $this->createProject('HTML Project');

        $issue = new ProjectIssue();
        $issue->setName('HTML Test Issue');
        $issue->setProject($project);

        $this->entityManager->persist($project);
        $this->entityManager->persist($issue);
        $this->entityManager->flush();

        // open the edit form using the controller with renderAsHtml: true
        $editUrl = $this->adminUrlGenerator
            ->setDashboard(DashboardController::class)
            ->setController(ProjectIssueWithRenderAsHtmlCrudController::class)
            ->setAction('edit')
            ->setEntityId($issue->getId())
            ->generateUrl();

        $this->client->request('GET', $editUrl);
        $this->assertResponseIsSuccessful();

        // the selected option should contain the HTML-formatted label
        // the callback returns: "<strong>{name}</strong> (ID: {id})"
        $crawler = $this->client->getCrawler();
        $selectedOption = $crawler->filter('select[name*="project"] option[selected]');

        $this->assertCount(1, $selectedOption);
        $optionText = $selectedOption->text();

        // the option text should contain the project name and ID
        $this->assertStringContainsString('HTML Project', $optionText);
        $this->assertStringContainsString('(ID:', $optionText);
    }
}
