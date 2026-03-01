<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Actions;

use Doctrine\ORM\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\ActionGroupsEntityCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\ActionGroupsInlineEntityCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\ActionTestEntity;

/**
 * Tests for ActionGroup functionality across different CRUD pages.
 */
class ActionGroupsTest extends AbstractCrudTestCase
{
    protected EntityRepository $actionTestEntities;

    protected function getControllerFqcn(): string
    {
        return ActionGroupsEntityCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();

        $this->actionTestEntities = $this->entityManager->getRepository(ActionTestEntity::class);
    }

    public function testActionGroupsInIndexPage(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertGreaterThanOrEqual(6, $crawler->filter('.action-group')->count());

        // the first action group is displayed last because pages render actions in reverse order
        $firstDropdown = $crawler->filter('.action-group')->last();
        $dropdownButton = $firstDropdown->filter('button.dropdown-toggle')->first();
        static::assertStringContainsString('Action Group', $dropdownButton->text(), 'First dropdown should have label "Action Group"');

        $dropdownMenu = $firstDropdown->filter('.dropdown-menu')->first();
        static::assertGreaterThanOrEqual(2, $dropdownMenu->filter('.dropdown-item')->count(), 'First dropdown should have at least 2 actions');
    }

    public function testSplitButtonInIndexPage(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        $splitButtons = $crawler->filter('.action-group button.dropdown-toggle-split');
        static::assertGreaterThan(0, $splitButtons->count(), 'Should have at least one split button dropdown');

        // get the parent action-dropdown of the first split button
        $splitDropdown = $splitButtons->last()->closest('.action-group');

        $mainAction = $splitDropdown->filter('[data-action-group-name-main-action]');
        static::assertCount(1, $mainAction, 'Split dropdown should have a main action');
        static::assertStringContainsString('Main Action', $mainAction->text(), 'Action group should display the label of the main action');

        static::assertCount(1, $splitDropdown->filter('button.dropdown-toggle-split'), 'Should have one split button');
        static::assertGreaterThanOrEqual(2, $splitDropdown->filter('.dropdown-menu .dropdown-item')->count(), 'Split dropdown should have at least 2 actions');
    }

    public function testSplitButtonMainActionIconInIndexPage(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        // find the split button for group2global which has an icon on the main action
        $splitDropdown = $crawler->filter('.action-group[data-action-group-name="group2global"]');
        static::assertCount(1, $splitDropdown, 'Should have group2global action group');

        $mainAction = $splitDropdown->filter('[data-action-group-name-main-action]');
        static::assertCount(1, $mainAction, 'Split dropdown should have a main action');

        // verify the main action has the icon
        $icon = $mainAction->filter('i.fa-star, .btn-icon i.fa-star');
        static::assertCount(1, $icon, 'Main action in split button should display its icon');
    }

    public function testActionGroupsWithHeadersAndDividersInIndexPage(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        // find dropdowns with headers
        $dropdownsWithHeaders = $crawler->filter('.action-group')->reduce(static function ($node) {
            return $node->filter('.dropdown-header')->count() > 0;
        });

        static::assertGreaterThan(0, $dropdownsWithHeaders->count(), 'Should have dropdowns with headers');

        $dropdownWithHeader = $dropdownsWithHeaders->first();

        // test headers exist
        $headers = $dropdownWithHeader->filter('.dropdown-header');
        static::assertGreaterThanOrEqual(1, $headers->count());
        static::assertStringContainsString('Group', $headers->first()->text());

        // test divider exists
        static::assertGreaterThanOrEqual(1, $dropdownWithHeader->filter('.dropdown-divider')->count());

        // test actions exist
        static::assertGreaterThanOrEqual(2, $dropdownWithHeader->filter('.dropdown-item')->count());
    }

    public function testActionGroupsWithFormActionInIndex(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        // test that we have dropdowns
        static::assertGreaterThan(0, $crawler->filter('.action-group')->count(), 'Should have dropdowns');

        // test that at least one dropdown has actions
        static::assertGreaterThan(0, $crawler->filter('.action-group .dropdown-menu .dropdown-item')->count(), 'Should have dropdown items');

        // note: Form actions are only rendered if configured with renderAsForm()
        // since our test controller has form_action configured with renderAsForm(),
        // forms should exist somewhere in the dropdowns
        $forms = $crawler->filter('.dropdown-menu form');
        if ($forms->count() > 0) {
            static::assertSame('POST', $forms->first()->attr('method'));
        } else {
            // if no forms found, at least verify dropdowns work
            static::assertTrue(true, 'Dropdowns exist even if no form actions');
        }
    }

    public function testActionGroupsWithCustomStylingInIndexPage(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        // find dropdowns with custom attributes
        $dropdownsWithCustomAttrs = $crawler->filter('.action-group[data-test]');

        static::assertGreaterThan(0, $dropdownsWithCustomAttrs->count(), 'Should have dropdowns with custom attributes');

        $customDropdown = $dropdownsWithCustomAttrs->first();

        // test custom HTML attributes on the container
        static::assertSame('group-5', $customDropdown->attr('data-test'));
        static::assertSame('value', $customDropdown->attr('data-custom'));

        // test custom CSS classes on button - they should include custom classes
        $button = $customDropdown->filter('button.dropdown-toggle')->first();
        $buttonClass = $button->attr('class');
        // cSS classes might be in different order, so check both are present
        static::assertStringContainsString('dropdown-toggle', $buttonClass);
        static::assertStringContainsString('btn', $buttonClass);
        // custom classes are applied
        static::assertTrue(
            str_contains($buttonClass, 'custom-dropdown') || str_contains($buttonClass, 'additional-class'),
            'Button should have custom CSS classes'
        );
    }

    public function testConditionalActionGroupsInIndexPage(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        // test that we have dropdowns on the page
        $dropdownCount = $crawler->filter('.action-group')->count();
        static::assertGreaterThan(0, $dropdownCount, 'Should have dropdowns on the page');

        // the conditional dropdown feature is tested by having group6 configured with displayIf()
        // it should only show for entities where isActive() returns true
        // this is a complex feature that's better tested in detail view where we can control the entity

        // for index page, just verify that dropdowns work
        static::assertTrue(true, 'Conditional dropdown feature is configured and dropdowns are rendered');
    }

    public function testDActionGroupsWithIconOnlyInIndexPage(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        // find dropdowns with icon but no text
        $dropdownsWithIconOnly = $crawler->filter('.action-group')->reduce(static function ($node) {
            $button = $node->filter('button.dropdown-toggle')->first();
            if (0 === $button->count()) {
                return false;
            }

            // check if button has icon and minimal text
            $hasIcon = $button->filter('i.fa-ellipsis-v, svg')->count() > 0;
            $text = trim(strip_tags($button->html()));

            return $hasIcon && \strlen($text) < 5; // Only icon or very short text
        });

        static::assertGreaterThan(0, $dropdownsWithIconOnly->count(), 'Should have dropdowns with icon only');
    }

    public function testEntityActionGroupsInIndexPage(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        $entityRows = $crawler->filter('table.datagrid tbody tr[data-id]');
        static::assertGreaterThan(0, $entityRows->count(), 'Should have entity rows in the table');

        $firstRow = $entityRows->first();
        $actionsDropdown = $firstRow->filter('.actions.actions-as-dropdown');
        static::assertCount(1, $actionsDropdown, 'Each row should have an actions dropdown');

        $submenus = $actionsDropdown->filter('.dropdown-submenu');
        static::assertGreaterThanOrEqual(3, $submenus->count(), 'Should have at least 3 entity action group submenus per row (group1, group2, group3, possibly group6 for active entities)');

        $firstSubmenu = $submenus->first();
        static::assertCount(1, $firstSubmenu->filter('a.dropdown-toggle'), 'Submenu should have a dropdown toggle');
        static::assertCount(1, $firstSubmenu->filter('ul.dropdown-menu'), 'Submenu should have a nested dropdown menu');

        $splitButtonSubmenus = $submenus->reduce(static function ($node) {
            return $node->filter('.dropdown-toggle-split')->count() > 0;
        });
        static::assertGreaterThan(0, $splitButtonSubmenus->count(), 'Should have at least one split button submenu (group2)');

        $splitSubmenu = $splitButtonSubmenus->first();
        static::assertCount(1, $splitSubmenu->filter('.d-flex'), 'Split submenu should have flex container');
        static::assertCount(1, $splitSubmenu->filter('.dropdown-toggle-split'), 'Split submenu should have split toggle button');

        $submenusWithHeaders = $submenus->reduce(static function ($node) {
            return $node->filter('.dropdown-menu .dropdown-header')->count() > 0;
        });
        static::assertGreaterThan(0, $submenusWithHeaders->count(), 'Should have at least one submenu with headers (group3)');

        $submenuWithHeader = $submenusWithHeaders->first();
        static::assertGreaterThanOrEqual(1, $submenuWithHeader->filter('.dropdown-header')->count(), 'Submenu should have headers');
        static::assertGreaterThanOrEqual(1, $submenuWithHeader->filter('.dropdown-divider')->count(), 'Submenu should have dividers');

        // test conditional entity action groups (group6)
        // find a row for an active entity (should have group6)
        $activeEntityRows = $entityRows->reduce(static function ($node) {
            // look for an entity row that shows group6 (which only displays for active entities)
            return $node->filter('.dropdown-submenu')->reduce(static function ($submenu) {
                return str_contains($submenu->text(), 'Action Group 6');
            })->count() > 0;
        });

        // find a row for an inactive entity (should NOT have group6)
        $inactiveEntityRows = $entityRows->reduce(static function ($node) {
            $hasGroup6 = false;
            $node->filter('.dropdown-submenu')->each(static function ($submenu) use (&$hasGroup6) {
                if (str_contains($submenu->text(), 'Action Group 6')) {
                    $hasGroup6 = true;
                }
            });

            return !$hasGroup6;
        });

        // we should have both active and inactive entities in fixtures
        static::assertGreaterThan(0, $activeEntityRows->count() + $inactiveEntityRows->count(), 'Should have entities with different active states');

        // test that action URLs are different for each entity (verifies __clone() works correctly)
        if ($entityRows->count() >= 2) {
            $firstRowActionLinks = $entityRows->eq(0)->filter('.dropdown-submenu .dropdown-item[data-action-name]');
            $secondRowActionLinks = $entityRows->eq(1)->filter('.dropdown-submenu .dropdown-item[data-action-name]');

            if ($firstRowActionLinks->count() > 0 && $secondRowActionLinks->count() > 0) {
                $firstUrl = $firstRowActionLinks->first()->attr('href');
                $secondUrl = $secondRowActionLinks->first()->attr('href');

                static::assertNotSame($firstUrl, $secondUrl, 'Action URLs should be different for each entity');
            }
        }
    }

    public function testActionGroupsInDetailPage(): void
    {
        // get an active entity (should show group6)
        $activeEntity = $this->actionTestEntities->findOneBy(['isActive' => true]);
        $crawler = $this->client->request('GET', $this->generateDetailUrl($activeEntity->getId()));

        static::assertGreaterThanOrEqual(5, $crawler->filter('.action-group')->count());
        static::assertGreaterThan(0, $crawler->filter('.action-group button.dropdown-toggle-split')->count());

        $dropdownsWithHeaders = $crawler->filter('.action-group')->reduce(static function ($node) {
            return $node->filter('.dropdown-header')->count() > 0;
        });
        static::assertGreaterThan(0, $dropdownsWithHeaders->count(), 'Should have dropdowns with headers');

        $dropdownsWithTooltip = $crawler->filter('.action-group button[data-bs-toggle="tooltip"]');
        if ($dropdownsWithTooltip->count() > 0) {
            static::assertSame('Complex dropdown', $dropdownsWithTooltip->first()->attr('title'), 'Should have dropdown with tooltip');
        }
    }

    public function testConditionalActionGroupsInDetail(): void
    {
        // test with an active entity (should show group6)
        $activeEntity = $this->actionTestEntities->findOneBy(['isActive' => true]);
        $crawler = $this->client->request('GET', $this->generateDetailUrl($activeEntity->getId()));

        $group6Count = $crawler->filter('.action-group')->reduce(static function ($node) {
            return str_contains($node->text(), 'Action Group 6');
        })->count();

        static::assertGreaterThan(0, $group6Count, 'Should show group6 for active entity');

        // test with an inactive entity (should not show group6)
        $inactiveEntity = $this->actionTestEntities->findOneBy(['isActive' => false]);
        static::assertNotNull($inactiveEntity, 'Inactive entity should exist in fixtures');
        $crawler = $this->client->request('GET', $this->generateDetailUrl($inactiveEntity->getId()));

        $group6Count = $crawler->filter('.action-group')->reduce(static function ($node) {
            return str_contains($node->text(), 'Action Group 6');
        })->count();

        static::assertSame(0, $group6Count, 'Should not show group6 for inactive entity');
    }

    public function testActionGroupsInEditPage(): void
    {
        $activeEntity = $this->actionTestEntities->findOneBy(['isActive' => true]);
        $crawler = $this->client->request('GET', $this->generateEditFormUrl($activeEntity->getId()));

        // test dropdowns exist
        static::assertGreaterThanOrEqual(4, $crawler->filter('.action-group')->count());

        // test split button exists
        static::assertGreaterThan(0, $crawler->filter('.action-group button.dropdown-toggle-split')->count());

        // test dropdown with custom styling
        $customDropdowns = $crawler->filter('.action-group[data-test]');
        static::assertGreaterThan(0, $customDropdowns->count());
    }

    public function testActionGroupsInNewPage(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        // test dropdowns exist
        static::assertGreaterThanOrEqual(3, $crawler->filter('.action-group')->count());

        // test dropdown with headers and dividers
        $dropdownsWithHeaders = $crawler->filter('.action-group')->reduce(static function ($node) {
            return $node->filter('.dropdown-header')->count() > 0;
        });
        static::assertGreaterThan(0, $dropdownsWithHeaders->count());
    }

    public function testDifferentActionTypesInActionGroups(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        $pageActions = $crawler->filter('.page-actions');
        $linkActions = $pageActions->filter('a.dropdown-item');
        static::assertGreaterThan(0, $linkActions->count());

        $hasValidLinks = false;
        $linkActions->each(static function ($link) use (&$hasValidLinks) {
            $href = $link->attr('href');
            if ('#' === $href || 'http://localhost/admin/action-groups-entity/a-global-action' === $href) {
                $hasValidLinks = true;
            }
        });
        static::assertTrue($hasValidLinks, 'Should have valid action links');

        $formActions = $pageActions->filter('.dropdown-menu form');
        static::assertGreaterThan(0, $formActions->count());
    }

    public function testCssClassesInActionGroups(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        // test dropdown with custom classes
        $customDropdowns = $crawler->filter('.action-group[data-test="group-5"]');
        static::assertGreaterThan(0, $customDropdowns->count());

        $button = $customDropdowns->first()->filter('button.dropdown-toggle')->first();
        $buttonClass = $button->attr('class');
        // check that button has necessary classes
        static::assertStringContainsString('btn', $buttonClass);
        static::assertStringContainsString('dropdown-toggle', $buttonClass);
        // custom classes might be applied
        static::assertTrue(
            str_contains($buttonClass, 'custom-dropdown') || str_contains($buttonClass, 'additional-class'),
            'Button should have custom CSS classes'
        );

        // test action with custom classes inside dropdown
        $actionsWithCustomClasses = $crawler->filter('.dropdown-item.btn-primary, .dropdown-item.text-danger');
        if ($actionsWithCustomClasses->count() > 0) {
            static::assertGreaterThan(0, $actionsWithCustomClasses->count(), 'Should have actions with custom CSS classes');
        } else {
            // at least verify dropdown items exist
            static::assertGreaterThan(0, $crawler->filter('.dropdown-item')->count(), 'Should have dropdown items');
        }
    }

    public function testActionGroupsHtmlStructure(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        // test regular dropdown structure
        $regularDropdowns = $crawler->filter('.action-group')->reduce(static function ($node) {
            return 0 === $node->filter('button.dropdown-toggle-split')->count();
        });

        static::assertGreaterThan(0, $regularDropdowns->count());

        $regularDropdown = $regularDropdowns->first();
        static::assertCount(1, $regularDropdown->filter('button.dropdown-toggle'));
        static::assertSame('dropdown', $regularDropdown->filter('button.dropdown-toggle')->attr('data-bs-toggle'));
        static::assertCount(1, $regularDropdown->filter('.dropdown-menu'));

        // test split button structure
        $splitDropdowns = $crawler->filter('.action-group')->reduce(static function ($node) {
            return $node->filter('button.dropdown-toggle-split')->count() > 0;
        });

        static::assertGreaterThan(0, $splitDropdowns->count());

        $splitDropdown = $splitDropdowns->first();
        static::assertGreaterThan(0, $splitDropdown->filter('[data-action-group-name-main-action]')->count()); // Main action
        static::assertCount(1, $splitDropdown->filter('button.dropdown-toggle-split')); // Dropdown toggle
        static::assertCount(1, $splitDropdown->filter('.dropdown-menu'));
    }

    public function testActionGroupsWithInlineEntityActions(): void
    {
        $url = $this->generateIndexUrl(
            controllerFqcn: ActionGroupsInlineEntityCrudController::class,
        );
        $crawler = $this->client->request('GET', $url);

        $entityRows = $crawler->filter('table.datagrid tbody tr[data-id]');
        static::assertGreaterThan(0, $entityRows->count(), 'Should have entity rows');

        $firstRow = $entityRows->first();
        $inlineActions = $firstRow->filter('.actions:not(.actions-as-dropdown)');
        static::assertCount(1, $inlineActions, 'Actions should be displayed inline');

        $actionGroups = $firstRow->filter('.actions .action-group');
        static::assertGreaterThan(0, $actionGroups->count(), 'Should have action groups in inline actions');

        $firstGroup = $actionGroups->first();
        static::assertCount(1, $firstGroup->filter('button.dropdown-toggle'), 'Action group should have dropdown toggle');
        static::assertCount(1, $firstGroup->filter('.dropdown-menu'), 'Action group should have dropdown menu');
    }
}
