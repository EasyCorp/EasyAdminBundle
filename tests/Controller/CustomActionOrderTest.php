<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Controller;

use Doctrine\ORM\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller\CustomActionOrderCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller\SecureDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Entity\Category;

class CustomActionOrderTest extends AbstractCrudTestCase
{
    protected EntityRepository $categories;

    protected function getControllerFqcn(): string
    {
        return CustomActionOrderCrudController::class;
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

        $this->categories = $this->entityManager->getRepository(Category::class);
    }

    public function testIndexPageGlobalActionsOrder(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        $globalActionNames = $crawler->filter('.global-actions [data-action-name]')->each(function ($node) {
            return $node->attr('data-action-name');
        });

        // expected order with smart sorting:
        // 1. Solid buttons first and then text buttons
        // 2. On each group, sort by button variant: Primary, Default, Success, Warning, Danger
        $expectedOrder = [
            'new',
            'global_primary',
            'global_success',
            'global_warning',
            'global_danger',
            'global_text',
        ];
        // in the template, we reverse the action order to show the most
        // important actions on the right and the rest to the left of it
        $actionsOrderInTemplate = array_reverse($expectedOrder);

        static::assertSame($actionsOrderInTemplate, $globalActionNames);
    }

    public function testIndexPageEntityActionsOrder(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        // get entity actions for the first row
        $entityActionNames = $crawler->filter('.datagrid tbody tr')->first()->filter('[data-action-name]')->each(function ($node) {
            return $node->attr('data-action-name');
        });

        // expected order with smart sorting:
        // 1. Solid buttons first and then text buttons
        // 2. On each group, sort by button variant: Primary, Default, Success, Warning, Danger
        $expectedOrder = [
            'primary_action',
            'edit',
            'detail',
            'default_action',
            'success_action',
            'warning_action',
            'danger_solid_action',
            'text_action',
            'delete',
            'danger_text_action',
        ];

        static::assertSame($expectedOrder, $entityActionNames);
    }

    public function testDetailPageActionsOrder(): void
    {
        $crawler = $this->client->request('GET', $this->generateDetailUrl($this->categories->findOneBy([])->getId()));

        // get all actions in detail page
        $actionNames = $crawler->filter('.page-actions [data-action-name]')->each(function ($node) {
            return $node->attr('data-action-name');
        });

        // expected order with smart sorting:
        // 1. Solid buttons first and then text buttons
        // 2. On each group, sort by button variant: Primary, Default, Success, Warning, Danger
        $expectedOrder = [
            'edit',
            'primary_action',
            'index',
            'success_action',
            'warning_action',
            'delete',
            'danger_text_action',
        ];
        // in the template, we reverse the action order to show the most
        // important actions on the right and the rest to the left of it
        $actionsOrderInTemplate = array_reverse($expectedOrder);

        static::assertSame($actionsOrderInTemplate, $actionNames);
    }

    public function testEditPageActionsOrder(): void
    {
        $crawler = $this->client->request('GET', $this->generateEditFormUrl($this->categories->findOneBy([])->getId()));

        // get form actions
        $actionNames = $crawler->filter('.page-actions [data-action-name]')->each(function ($node) {
            return $node->attr('data-action-name');
        });

        // expected order with smart sorting:
        // 1. Solid buttons first and then text buttons
        // 2. On each group, sort by button variant: Primary, Default, Success, Warning, Danger
        $expectedOrder = [
            'saveAndReturn',
            'primary_action',
            'saveAndContinue',
            'success_action',
            'warning_action',
            'danger_text_action',
        ];
        // in the template, we reverse the action order to show the most
        // important actions on the right and the rest to the left of it
        $actionsOrderInTemplate = array_reverse($expectedOrder);

        static::assertSame($actionsOrderInTemplate, $actionNames);
    }

    public function testNewPageActionsOrder()
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        // get form actions
        $actionNames = $crawler->filter('.page-actions [data-action-name]')->each(function ($node) {
            return $node->attr('data-action-name');
        });

        // expected order with smart sorting:
        // 1. Solid buttons first and then text buttons
        // 2. On each group, sort by button variant: Primary, Default, Success, Warning, Danger
        $expectedOrder = [
            'saveAndReturn',
            'primary_action',
            'saveAndAddAnother',
            'success_action',
            'warning_action',
            'danger_text_action',
        ];
        // in the template, we reverse the action order to show the most
        // important actions on the right and the rest to the left of it
        $actionsOrderInTemplate = array_reverse($expectedOrder);

        static::assertSame($actionsOrderInTemplate, $actionNames);
    }
}
