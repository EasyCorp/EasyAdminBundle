<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Actions;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Actions\ActionsOrderingCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\CategoryFactory;

class ActionsOrderingTest extends AbstractCrudTestCase
{
    protected function getControllerFqcn(): string
    {
        return ActionsOrderingCrudController::class;
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

    public function testIndexPageGlobalActionsOrder(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        $globalActionNames = $crawler->filter('.global-actions [data-action-name]')->each(static function ($node) {
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
        $entity = CategoryFactory::createOne();

        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        $row = $crawler->filter(sprintf('.datagrid tbody tr[data-id="%d"]', $entity->getId()));
        static::assertCount(1, $row);

        $entityActionNames = $row->filter('[data-action-name]')->each(static function ($node) {
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
        $entity = CategoryFactory::createOne();

        $crawler = $this->client->request('GET', $this->generateDetailUrl($entity->getId()));

        $actionNames = $crawler->filter('.page-actions [data-action-name]')->each(static function ($node) {
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
        $entity = CategoryFactory::createOne();

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($entity->getId()));

        $actionNames = $crawler->filter('.page-actions [data-action-name]')->each(static function ($node) {
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

    public function testNewPageActionsOrder(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $actionNames = $crawler->filter('.page-actions [data-action-name]')->each(static function ($node) {
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
