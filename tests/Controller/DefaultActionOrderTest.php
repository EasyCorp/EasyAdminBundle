<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Controller;

use Doctrine\ORM\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller\BlogPostCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller\SecureDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Entity\BlogPost;

class DefaultActionOrderTest extends AbstractCrudTestCase
{
    protected EntityRepository $blogPosts;

    protected function getControllerFqcn(): string
    {
        return BlogPostCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return SecureDashboardController::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();
        $this->client->setServerParameters(['PHP_AUTH_USER' => 'admin', 'PHP_AUTH_PW' => '1234']);

        $this->blogPosts = $this->entityManager->getRepository(BlogPost::class);
    }

    public function testIndexPageGlobalActionsOrder(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        $globalActionNames = [];
        $crawler->filter('.global-actions [data-action-name]')->each(function ($node) use (&$globalActionNames) {
            $globalActionNames[] = $node->attr('data-action-name');
        });

        // default global action in index is only "new" which is primary
        static::assertSame(['new'], $globalActionNames);
    }

    public function testIndexPageEntityActionsOrder(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        // get entity actions for the first row
        $entityActionNames = [];
        $crawler->filter('.datagrid tbody tr')->first()->filter('[data-action-name]')->each(function ($node) use (&$entityActionNames) {
            $actionName = $node->attr('data-action-name');
            if (!\in_array($actionName, $entityActionNames, true)) {
                $entityActionNames[] = $actionName;
            }
        });

        // expected order for index entity actions (with smart sorting):
        // 1. edit (primary solid)
        // 2. delete (danger text)
        // (note: detail action is not shown in index by default)
        $expectedOrder = ['edit', 'delete'];
        static::assertSame($expectedOrder, $entityActionNames);
    }

    public function testDetailPageActionsOrder(): void
    {
        $entities = $this->blogPosts->findAll();

        if (empty($entities)) {
            $this->markTestSkipped('No blog posts found in the database');
        }

        $entity = $entities[0];

        $crawler = $this->client->request('GET', $this->generateDetailUrl($entity->getId()));
        $actionNames = $crawler->filter('.page-actions [data-action-name]')->each(function ($node) {
            return $node->attr('data-action-name');
        });

        // expected order for detail actions (with smart sorting):
        // 1. edit (primary solid in detail page)
        // 2. index (default solid)
        // 3. delete (danger text)
        $expectedOrder = ['edit', 'index', 'delete'];
        // in the template, we reverse the action order to show the most
        // important actions on the right and the rest to the left of it
        $actionsOrderInTemplate = array_reverse($expectedOrder);
        static::assertSame($actionsOrderInTemplate, $actionNames);
    }

    public function testEditPageActionsOrder(): void
    {
        $crawler = $this->client->request('GET', $this->generateEditFormUrl($this->blogPosts->findOneBy([])->getId()));

        // get form actions
        $actionNames = $crawler->filter('.page-actions [data-action-name]')->each(function ($node) {
            return $node->attr('data-action-name');
        });

        // expected order for edit actions (with smart sorting):
        // 1. saveAndReturn (primary solid)
        // 2. saveAndContinue (default solid)
        $expectedOrder = ['saveAndReturn', 'saveAndContinue'];
        // in the template, we reverse the action order to show the most
        // important actions on the right and the rest to the left of it
        $actionsOrderInTemplate = array_reverse($expectedOrder);
        static::assertSame($actionsOrderInTemplate, $actionNames);
    }

    public function testNewPageActionsOrder(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        // get form actions
        $actionNames = $crawler->filter('.page-actions [data-action-name]')->each(function ($node) {
            return $node->attr('data-action-name');
        });

        // expected order for new actions (with smart sorting):
        // 1. saveAndReturn (primary solid)
        // 2. saveAndAddAnother (default solid)
        // (note: saveAndContinue action is not shown in new page by default)
        $expectedOrder = ['saveAndReturn', 'saveAndAddAnother'];
        // in the template, we reverse the action order to show the most
        // important actions on the right and the rest to the left of it
        $actionsOrderInTemplate = array_reverse($expectedOrder);
        static::assertSame($actionsOrderInTemplate, $actionNames);
    }
}
