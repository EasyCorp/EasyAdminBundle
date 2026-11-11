<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Actions;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Actions\ActionGroupsMainActionCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Actions\ActionGroupsMainActionInlineCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\CategoryFactory;
use Symfony\Component\DomCrawler\Crawler;

/**
 * The main action of an action group is hidden like any other action: when its
 * displayIf() callable returns false, when the user lacks its permission or when
 * the action is disabled.
 */
class ActionGroupsMainActionTest extends AbstractCrudTestCase
{
    protected function getControllerFqcn(): string
    {
        return ActionGroupsMainActionCrudController::class;
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

    public function testMainActionWithDisplayIfInDetailPage(): void
    {
        $activeEntity = CategoryFactory::createOne(['active' => true]);
        $inactiveEntity = CategoryFactory::createOne(['active' => false]);

        $crawler = $this->client->request('GET', $this->generateDetailUrl($activeEntity->getId()));
        $group = $this->findGroup($crawler, 'conditional_main_group');
        static::assertCount(1, $group->filter('.action-conditional_main[data-action-group-name-main-action]'), 'The main action is displayed when its condition is met');
        static::assertCount(1, $group->filter('button.dropdown-toggle-split'));
        static::assertCount(1, $group->filter('.dropdown-menu .action-conditional_main_item'));

        $crawler = $this->client->request('GET', $this->generateDetailUrl($inactiveEntity->getId()));
        $group = $this->findGroup($crawler, 'conditional_main_group');
        $this->assertGroupIsRenderedAsPlainDropdown($group, 'Conditional Main Group', 'conditional_main', 'conditional_main_item');
    }

    public function testMainActionWithoutPermissionInDetailPage(): void
    {
        $entity = CategoryFactory::createOne(['active' => true]);
        $crawler = $this->client->request('GET', $this->generateDetailUrl($entity->getId()));

        $this->assertGroupIsRenderedAsPlainDropdown($this->findGroup($crawler, 'restricted_main_group'), 'Restricted Main Group', 'restricted_main', 'restricted_main_item');
    }

    public function testDisabledMainActionInDetailPage(): void
    {
        $entity = CategoryFactory::createOne(['active' => true]);
        $crawler = $this->client->request('GET', $this->generateDetailUrl($entity->getId()));

        $this->assertGroupIsRenderedAsPlainDropdown($this->findGroup($crawler, 'disabled_main_group'), 'Disabled Main Group', 'disabled_main', 'disabled_main_item');
    }

    public function testGroupWithOnlyTheMainActionVisibleInDetailPage(): void
    {
        $activeEntity = CategoryFactory::createOne(['active' => true]);
        $inactiveEntity = CategoryFactory::createOne(['active' => false]);

        $crawler = $this->client->request('GET', $this->generateDetailUrl($activeEntity->getId()));
        $group = $this->findGroup($crawler, 'only_main_group');
        static::assertCount(1, $group->filter('button.dropdown-toggle-split'));
        static::assertCount(1, $group->filter('.dropdown-menu .action-only_main_item'));

        $crawler = $this->client->request('GET', $this->generateDetailUrl($inactiveEntity->getId()));
        $this->assertGroupIsRenderedAsSingleButton($this->findGroup($crawler, 'only_main_group'), 'only_main', 'Only Main');
    }

    public function testGroupWithNothingVisibleInDetailPage(): void
    {
        $activeEntity = CategoryFactory::createOne(['active' => true]);
        $inactiveEntity = CategoryFactory::createOne(['active' => false]);

        $crawler = $this->client->request('GET', $this->generateDetailUrl($activeEntity->getId()));
        static::assertCount(1, $this->findGroup($crawler, 'nothing_visible_group')->filter('[data-action-group-name-main-action]'));

        $crawler = $this->client->request('GET', $this->generateDetailUrl($inactiveEntity->getId()));
        static::assertCount(0, $crawler->filter('.action-group[data-action-group-name="nothing_visible_group"]'));
        static::assertCount(0, $crawler->filter('.action-nothing_visible_main'));
    }

    public function testGlobalGroupsInIndexPage(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());
        $globalActions = $crawler->filter('.global-actions');

        $this->assertGroupIsRenderedAsPlainDropdown($this->findGroup($globalActions, 'restricted_main_global_group'), 'Restricted Main Global Group', 'restricted_main', 'restricted_main_global_item');
        $this->assertGroupIsRenderedAsSingleButton($this->findGroup($globalActions, 'only_main_global_group'), 'only_main_global', 'Only Main Global');
    }

    /**
     * The index page processes the same action groups for every row, so the main
     * action hidden in one row must still be displayed in the other rows.
     */
    public function testEntityGroupsInIndexPageDropdown(): void
    {
        // the inactive entity is created (and rendered) first on purpose
        $inactiveEntity = CategoryFactory::createOne(['active' => false]);
        $activeEntity = CategoryFactory::createOne(['active' => true]);
        $otherInactiveEntity = CategoryFactory::createOne(['active' => false]);

        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        foreach ([$inactiveEntity, $otherInactiveEntity] as $entity) {
            $row = $crawler->filter(sprintf('table.datagrid tbody tr[data-id="%d"] .actions-as-dropdown', $entity->getId()));
            static::assertCount(1, $row);

            static::assertCount(0, $row->filter('.action-conditional_main'), 'The main action is hidden when its condition is not met');
            $submenu = $this->findSubmenu($row, 'Conditional Main Group');
            static::assertCount(0, $submenu->filter('.dropdown-toggle-split'));
            static::assertCount(1, $submenu->filter('.dropdown-menu .action-conditional_main_item'));

            // the group with only its main action is a regular item, not a submenu
            $onlyMainItem = $row->filter('.dropdown-item.action-only_main');
            static::assertCount(1, $onlyMainItem);
            static::assertSame('Only Main', $onlyMainItem->filter('.action-label')->text(), 'The item should be rendered like the items of regular actions');
            static::assertStringContainsString('dropdown-item-variant-', $onlyMainItem->attr('class'));
            static::assertCount(0, $row->filter('.dropdown-submenu .action-only_main'));
            static::assertCount(0, $row->filter('.action-only_main_item'));
            static::assertStringNotContainsString('Only Main Header', $row->text());

            static::assertCount(0, $row->filter('.action-nothing_visible_main'));
            static::assertStringNotContainsString('Nothing Visible Group', $row->text());
        }

        $row = $crawler->filter(sprintf('table.datagrid tbody tr[data-id="%d"] .actions-as-dropdown', $activeEntity->getId()));
        static::assertCount(1, $row->filter('.dropdown-submenu .dropdown-item.action-conditional_main'), 'The main action is displayed when its condition is met');
        static::assertCount(1, $row->filter('.dropdown-submenu .dropdown-item.action-only_main'));
        static::assertCount(1, $row->filter('.dropdown-submenu .dropdown-menu .action-only_main_item'));
        static::assertCount(1, $row->filter('.dropdown-submenu .dropdown-item.action-nothing_visible_main'));

        // permissions and disabled actions don't depend on the entity
        static::assertCount(0, $crawler->filter('table.datagrid .action-restricted_main'));
        static::assertCount(0, $crawler->filter('table.datagrid .action-disabled_main'));
        static::assertCount(0, $this->findSubmenu($row, 'Restricted Main Group')->filter('.dropdown-toggle-split'));
        static::assertCount(1, $this->findSubmenu($row, 'Restricted Main Group')->filter('.dropdown-menu .action-restricted_main_item'));
        static::assertCount(0, $this->findSubmenu($row, 'Disabled Main Group')->filter('.dropdown-toggle-split'));
        static::assertCount(1, $this->findSubmenu($row, 'Disabled Main Group')->filter('.dropdown-menu .action-disabled_main_item'));
    }

    public function testEntityGroupsInIndexPageInlined(): void
    {
        $inactiveEntity = CategoryFactory::createOne(['active' => false]);
        $activeEntity = CategoryFactory::createOne(['active' => true]);

        $crawler = $this->client->request('GET', $this->generateIndexUrl(controllerFqcn: ActionGroupsMainActionInlineCrudController::class));

        $inactiveRow = $crawler->filter(sprintf('table.datagrid tbody tr[data-id="%d"] .actions', $inactiveEntity->getId()));
        $this->assertGroupIsRenderedAsPlainDropdown($this->findGroup($inactiveRow, 'conditional_main_group'), 'Conditional Main Group', 'conditional_main', 'conditional_main_item');
        $this->assertGroupIsRenderedAsSingleButton($this->findGroup($inactiveRow, 'only_main_group'), 'only_main', 'Only Main');
        static::assertCount(0, $inactiveRow->filter('.action-group[data-action-group-name="nothing_visible_group"]'));

        $activeRow = $crawler->filter(sprintf('table.datagrid tbody tr[data-id="%d"] .actions', $activeEntity->getId()));
        static::assertCount(1, $this->findGroup($activeRow, 'conditional_main_group')->filter('.action-conditional_main[data-action-group-name-main-action]'));
        static::assertCount(1, $this->findGroup($activeRow, 'only_main_group')->filter('button.dropdown-toggle-split'));
        static::assertCount(1, $this->findGroup($activeRow, 'nothing_visible_group')->filter('[data-action-group-name-main-action]'));
    }

    private function findGroup(Crawler $crawler, string $groupName): Crawler
    {
        $group = $crawler->filter(sprintf('.action-group[data-action-group-name="%s"]', $groupName));
        static::assertCount(1, $group, sprintf('The "%s" action group should be rendered once', $groupName));

        return $group;
    }

    private function findSubmenu(Crawler $row, string $groupLabel): Crawler
    {
        $submenu = $row->filter('.dropdown-submenu')->reduce(static fn (Crawler $node): bool => str_contains($node->filter('a.dropdown-toggle')->text(), $groupLabel));
        static::assertCount(1, $submenu, sprintf('The "%s" action group should be rendered as a submenu', $groupLabel));

        return $submenu;
    }

    private function assertGroupIsRenderedAsPlainDropdown(Crawler $group, string $groupLabel, string $mainActionName, string $itemName): void
    {
        static::assertCount(0, $group->filter('[data-action-group-name-main-action]'), 'The hidden main action should not be rendered');
        static::assertCount(0, $group->filter('.action-'.$mainActionName));
        static::assertCount(0, $group->filter('.dropdown-toggle-split'));

        $toggle = $group->filter('button.dropdown-toggle');
        static::assertCount(1, $toggle);
        static::assertStringContainsString($groupLabel, $toggle->text(), 'The toggle should display the label of the group');
        static::assertCount(1, $group->filter('.dropdown-menu .action-'.$itemName));
    }

    private function assertGroupIsRenderedAsSingleButton(Crawler $group, string $mainActionName, string $mainActionLabel): void
    {
        $mainAction = $group->filter(sprintf('.action-%s[data-action-group-name-main-action]', $mainActionName));
        static::assertCount(1, $mainAction);
        static::assertStringContainsString($mainActionLabel, $mainAction->text());
        static::assertCount(0, $group->filter('.dropdown-toggle'), 'A group without dropdown actions should not render the toggle');
        static::assertCount(0, $group->filter('.dropdown-menu'), 'A group without dropdown actions should not render an empty menu');
    }
}
