<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Autocomplete;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\ProjectDomain\ProjectCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\ProjectDomain\ProjectIssueWithAutocompleteCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\ProjectDomain\ProjectIssueWithRenderAsHtmlCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\ProjectFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\ProjectIssueFactory;

class AutocompleteCustomizationTest extends AbstractCrudTestCase
{
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
    }

    public function testAutocompleteWithFieldLevelCallback(): void
    {
        $project = ProjectFactory::createOne(['name' => 'Unicorn Sparkle']);
        ProjectFactory::createOne(['name' => 'Rainbow Dash']);

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

        $this->client->request('GET', $autocompleteUrl);
        $this->assertResponseIsSuccessful();

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('results', $data);
        $this->assertCount(1, $data['results']);
        $this->assertSame(sprintf('Project: Unicorn Sparkle (ID: %d)', $project->getId()), $data['results'][0]['entityAsString']);
    }

    public function testAutocompleteWithCrudLevelCallback(): void
    {
        $issue = ProjectIssueFactory::createOne(['name' => 'Bug #1']);

        // the 'name' field has no field-level autocomplete callback, so the
        // CRUD-level callback defined in configureCrud() is used
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
        $this->assertResponseIsSuccessful();

        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertCount(1, $data['results']);
        $this->assertSame(sprintf('[%d] Bug #1', $issue->getId()), $data['results'][0]['entityAsString']);
    }

    public function testAutocompleteEscapesHtmlByDefault(): void
    {
        ProjectFactory::createOne(['name' => '<script>alert("XSS")</script>Malicious Project']);

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

        $this->assertCount(1, $data['results']);
        $firstResult = $data['results'][0]['entityAsString'];

        $this->assertStringContainsString('&lt;script&gt;', $firstResult);
        $this->assertStringNotContainsString('<script>', $firstResult);
    }

    public function testAutocompleteReturnsValidJsonStructure(): void
    {
        if (!class_exists(PostgreSQLPlatform::class)) {
            $this->markTestSkipped('Doctrine DBAL 3.x+ is required.');
        }

        $project = ProjectFactory::createOne(['name' => 'JSON Test Project']);

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
        $this->assertArrayHasKey('next_page', $data);
        $this->assertCount(1, $data['results']);
        $this->assertSame((string) $project->getId(), $data['results'][0]['entityId']);
        $this->assertArrayHasKey('entityAsString', $data['results'][0]);
    }

    public function testAutocompleteWithCallbackAndRenderAsHtml(): void
    {
        ProjectFactory::createOne(['name' => 'Important Project']);

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
        $this->assertCount(1, $data['results']);

        $firstResult = $data['results'][0]['entityAsString'];

        // when renderAsHtml: true, HTML tags should NOT be escaped
        $this->assertStringContainsString('<strong>', $firstResult);
        $this->assertStringContainsString('</strong>', $firstResult);
        $this->assertStringNotContainsString('&lt;strong&gt;', $firstResult);
        $this->assertStringContainsString('Important Project', $firstResult);
    }

    public function testSelectedItemRenderedSameAsDropdownEntries(): void
    {
        $project = ProjectFactory::createOne(['name' => 'Selected Project']);
        $issue = ProjectIssueFactory::createOne(['name' => 'Test Issue', 'project' => $project]);

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
        $project = ProjectFactory::createOne(['name' => 'HTML Project']);
        $issue = ProjectIssueFactory::createOne(['name' => 'HTML Test Issue', 'project' => $project]);

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

        $this->assertStringContainsString('HTML Project', $optionText);
        $this->assertStringContainsString(sprintf('(ID: %d)', $project->getId()), $optionText);
    }
}
