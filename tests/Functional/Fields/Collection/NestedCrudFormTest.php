<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Fields\Collection;

use Doctrine\ORM\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\NestedCrudForm\ProjectIssueNestedCrudController;
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
     * Tests that the embedded CRUD controller's createEntity() override shapes the
     * prototype HTML, so users see their defaults in the rendered form (see #6991).
     */
    public function testNestedCreateEntityShapesPrototypeHtml(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());
        static::assertResponseIsSuccessful();

        $prototype = $crawler->filter('[data-prototype]');
        static::assertGreaterThanOrEqual(1, $prototype->count(), 'A collection prototype should exist');
        static::assertStringContainsString(
            ProjectIssueNestedCrudController::DEFAULT_ENTRY_NAME,
            $prototype->attr('data-prototype'),
            'createEntity() defaults from the embedded controller should be reflected in the prototype HTML',
        );
    }

    /**
     * Tests that the embedded CRUD controller's createEntity() is reached when Symfony
     * Form binds a new (empty) collection entry on submit, i.e. that
     * `entry_options.empty_data` is wired in addition to `prototype_data` (see #6991).
     */
    public function testNestedCreateEntityIsCalledForEmptyDataOnSubmit(): void
    {
        $project = $this->createProjectWithIssues();

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($project->getId()));
        static::assertResponseIsSuccessful();

        $form = $crawler->filter('form[name="Project"]')->form();
        $values = $form->getPhpValues();
        // simulate the JS "add entry" behaviour: append an entry with no submitted fields
        // so Symfony Form has to invoke entry_options.empty_data to materialise the new
        // ProjectIssue (which then routes to the embedded controller's createEntity()).
        $values['Project']['projectIssues'][] = [];

        $callsBeforeSubmit = ProjectIssueNestedCrudController::$createEntityCallCount;
        try {
            // form binding may fail on this fixture (the new ProjectIssue's `name` is
            // required and not submitted, so PropertyAccessor will refuse to set null).
            // We are not testing the persistence side here, only that empty_data was
            // reached before the binding step ran.
            $this->client->catchExceptions(false);
            $this->client->request($form->getMethod(), $form->getUri(), $values, [], ['CONTENT_TYPE' => 'application/x-www-form-urlencoded']);
        } catch (\Throwable) {
            // expected on this fixture, see comment above.
        }

        static::assertGreaterThan(
            $callsBeforeSubmit,
            ProjectIssueNestedCrudController::$createEntityCallCount,
            'createEntity() should be invoked through entry_options.empty_data when a new entry is bound',
        );
    }

    /**
     * Tests that renderExpanded(true) on CollectionField renders entries with the
     * accordion already expanded (Bootstrap "show" class on the collapse wrapper and
     * no "collapsed" class on the toggle button).
     */
    public function testRenderExpandedOnCollectionField(): void
    {
        $project = $this->createProjectWithIssues();

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($project->getId()));

        static::assertResponseIsSuccessful();

        $collapseWrappers = $crawler->filter('.field-collection .accordion-collapse');
        static::assertGreaterThan(0, $collapseWrappers->count(), 'At least one accordion-collapse wrapper should be rendered');

        foreach ($collapseWrappers as $wrapper) {
            static::assertStringContainsString(
                'show',
                $wrapper->getAttribute('class'),
                'renderExpanded(true) should add the "show" class on .accordion-collapse',
            );
        }

        foreach ($crawler->filter('.field-collection .accordion-button') as $button) {
            static::assertStringNotContainsString(
                'collapsed',
                $button->getAttribute('class'),
                'renderExpanded(true) should not add the "collapsed" class on .accordion-button',
            );
        }
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
