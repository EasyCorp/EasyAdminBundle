<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Fields\Collection;

use Doctrine\ORM\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\NestedCrudForm\ProjectWithNestedIssuesCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\ProjectDomain\Project;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\ProjectDomain\ProjectIssue;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Tests that CollectionField with useEntryCrudForm() works correctly when the nested
 * CRUD controller accesses $context->getEntity() in configureFields().
 */
class NestedCrudFormTest extends AbstractCrudTestCase
{
    /** @var EntityRepository<Project> */
    private EntityRepository $projectRepository;

    protected function setUp(): void
    {
        // useEntryCrudForm() requires the 'prototype_options' option, added in Symfony 6.1
        if (Kernel::MAJOR_VERSION < 6 || (6 === Kernel::MAJOR_VERSION && Kernel::MINOR_VERSION < 1)) {
            $this->markTestSkipped('useEntryCrudForm() requires Symfony 6.1 or newer.');
        }

        parent::setUp();
        $this->client->followRedirects();
        $this->projectRepository = $this->entityManager->getRepository(Project::class);
    }

    protected function tearDown(): void
    {
        unset($this->projectRepository);
        parent::tearDown();
    }

    protected function getControllerFqcn(): string
    {
        return ProjectWithNestedIssuesCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    /**
     * Tests that the NEW form page loads correctly with nested CRUD forms.
     *
     * The nested ProjectIssueNestedCrudController accesses $context->getEntity() in
     * configureFields(). Before the fix, this would fail because the context would
     * contain the parent entity (Project) instead of the nested entity (ProjectIssue).
     */
    public function testNewFormLoadsWithNestedCrudForm(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        static::assertResponseIsSuccessful();

        $form = $crawler->filter('form[name="Project"]');
        static::assertCount(1, $form, 'The Project form should exist');

        $collectionField = $crawler->filter('.field-collection');
        static::assertGreaterThanOrEqual(1, $collectionField->count(), 'The collection field should exist');
    }

    /**
     * Tests that the EDIT form page loads correctly with nested CRUD forms.
     */
    public function testEditFormLoadsWithNestedCrudForm(): void
    {
        $project = $this->createProjectWithIssues();

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($project->getId()));

        static::assertResponseIsSuccessful();

        $form = $crawler->filter('form[name="Project"]');
        static::assertCount(1, $form, 'The Project form should exist');

        $html = $crawler->html();
        static::assertStringContainsString('Test Issue 1', $html, 'First issue should be visible');
        static::assertStringContainsString('Test Issue 2', $html, 'Second issue should be visible');
    }

    /**
     * Creates a Project with ProjectIssue entities for testing.
     */
    private function createProjectWithIssues(): Project
    {
        $project = new Project();
        $project->setName('Test Project');
        $project->setDescription('Test project for nested CRUD form testing');
        $project->setInternal(false);
        $project->setCountInteger(1);
        $project->setCountSmallint(1);
        $project->setPriceDecimal('100');
        $project->setPriceFloat(100.0);
        $project->setStartDateMutable(new \DateTime());
        $project->setStartDateImmutable(new \DateTimeImmutable());
        $project->setStartDateTimeMutable(new \DateTime());
        $project->setStartDateTimeImmutable(new \DateTimeImmutable());
        $project->setStartDateTimeTzMutable(new \DateTime());
        $project->setStartDateTimeTzImmutable(new \DateTimeImmutable());
        $project->setStartTimeMutable(new \DateTime());
        $project->setStartTimeImmutable(new \DateTimeImmutable());
        $project->setStatesSimpleArray(['active']);
        $project->setRolesJson(['ROLE_USER']);

        $issue1 = new ProjectIssue();
        $issue1->setName('Test Issue 1');
        $project->addProjectIssue($issue1);

        $issue2 = new ProjectIssue();
        $issue2->setName('Test Issue 2');
        $project->addProjectIssue($issue2);

        $this->entityManager->persist($project);
        $this->entityManager->flush();

        return $project;
    }
}
