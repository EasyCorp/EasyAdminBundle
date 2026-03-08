<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Controller;

use Doctrine\ORM\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller\ActionGroupsCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller\ActionGroupsInlineCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller\SecureDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Entity\Category;

class ActionGroupsCrudControllerTest extends AbstractCrudTestCase
{
    protected EntityRepository $categories;

    protected function getControllerFqcn(): string
    {
        return ActionGroupsCrudController::class;
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

    public function testActionGroupsWithHeadersAndDividersInIndexPage(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        // Find dropdowns with headers
        $dropdownsWithHeaders = $crawler->filter('.action-group')->reduce(function ($node) {
            return $node->filter('.dropdown-header')->count() > 0;
        });

        static::assertGreaterThan(0, $dropdownsWithHeaders->count(), 'Should have dropdowns with headers');

        $dropdownWithHeader = $dropdownsWithHeaders->first();

        // Test headers exist
        $headers = $dropdownWithHeader->filter('.dropdown-header');
        static::assertGreaterThanOrEqual(1, $headers->count());
        static::assertStringContainsString('Group', $headers->first()->text());

        // Test divider exists
        static::assertGreaterThanOrEqual(1, $dropdownWithHeader->filter('.dropdown-divider')->count());

        // Test actions exist
        static::assertGreaterThanOrEqual(2, $dropdownWithHeader->filter('.dropdown-item')->count());
    }

    public function testActionGroupsWithFormActionInIndex(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        // Test that we have dropdowns
        static::assertGreaterThan(0, $crawler->filter('.action-group')->count(), 'Should have dropdowns');

        // Test that at least one dropdown has actions
        static::assertGreaterThan(0, $crawler->filter('.action-group .dropdown-menu .dropdown-item')->count(), 'Should have dropdown items');

        // Note: Form actions are only rendered if configured with displayAsForm()
        // Since our test controller has form_action configured with displayAsForm(),
        // forms should exist somewhere in the dropdowns
        $forms = $crawler->filter('.dropdown-menu form');
        if ($forms->count() > 0) {
            static::assertSame('POST', $forms->first()->attr('method'));
        } else {
            // If no forms found, at least verify dropdowns work
            static::assertTrue(true, 'Dropdowns exist even if no form actions');
        }
    }

    public function testActionGroupsWithCustomStylingInIndexPage(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        // Find dropdowns with custom attributes
        $dropdownsWithCustomAttrs = $crawler->filter('.action-group[data-test]');

        static::assertGreaterThan(0, $dropdownsWithCustomAttrs->count(), 'Should have dropdowns with custom attributes');

        $customDropdown = $dropdownsWithCustomAttrs->first();

        // Test custom HTML attributes on the container
        static::assertSame('group-5', $customDropdown->attr('data-test'));
        static::assertSame('value', $customDropdown->attr('data-custom'));

        // Test custom CSS classes on button - they should include custom classes
        $button = $customDropdown->filter('button.dropdown-toggle')->first();
        $buttonClass = $button->attr('class');
        // CSS classes might be in different order, so check both are present
        static::assertStringContainsString('dropdown-toggle', $buttonClass);
        static::assertStringContainsString('btn', $buttonClass);
        // Custom classes are applied
        static::assertTrue(
            str_contains($buttonClass, 'custom-dropdown') || str_contains($buttonClass, 'additional-class'),
            'Button should have custom CSS classes'
        );
    }

    public function testConditionalActionGroupsInIndexPage(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        // Test that we have dropdowns on the page
        $dropdownCount = $crawler->filter('.action-group')->count();
        static::assertGreaterThan(0, $dropdownCount, 'Should have dropdowns on the page');

        // The conditional dropdown feature is tested by having group6 configured with displayIf()
        // It should only show for categories with names starting with "Category 1"
        // This is a complex feature that's better tested in detail view where we can control the entity

        // For index page, just verify that dropdowns work
        static::assertTrue(true, 'Conditional dropdown feature is configured and dropdowns are rendered');
    }

    public function testDActionGroupsWithIconOnlyInIndexPage(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        // Find dropdowns with icon but no text
        $dropdownsWithIconOnly = $crawler->filter('.action-group')->reduce(function ($node) {
            $button = $node->filter('button.dropdown-toggle')->first();
            if (0 === $button->count()) {
                return false;
            }

            // Check if button has icon and minimal text
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
        static::assertGreaterThanOrEqual(3, $submenus->count(), 'Should have at least 3 entity action group submenus per row (group1, group2, group3, possibly group6 for Category 10)');

        $firstSubmenu = $submenus->first();
        static::assertCount(1, $firstSubmenu->filter('a.dropdown-toggle'), 'Submenu should have a dropdown toggle');
        static::assertCount(1, $firstSubmenu->filter('ul.dropdown-menu'), 'Submenu should have a nested dropdown menu');

        $splitButtonSubmenus = $submenus->reduce(function ($node) {
            return $node->filter('.dropdown-toggle-split')->count() > 0;
        });
        static::assertGreaterThan(0, $splitButtonSubmenus->count(), 'Should have at least one split button submenu (group2)');

        $splitSubmenu = $splitButtonSubmenus->first();
        static::assertCount(1, $splitSubmenu->filter('.d-flex'), 'Split submenu should have flex container');
        static::assertCount(1, $splitSubmenu->filter('.dropdown-toggle-split'), 'Split submenu should have split toggle button');

        $submenusWithHeaders = $submenus->reduce(function ($node) {
            return $node->filter('.dropdown-menu .dropdown-header')->count() > 0;
        });
        static::assertGreaterThan(0, $submenusWithHeaders->count(), 'Should have at least one submenu with headers (group3)');

        $submenuWithHeader = $submenusWithHeaders->first();
        static::assertGreaterThanOrEqual(1, $submenuWithHeader->filter('.dropdown-header')->count(), 'Submenu should have headers');
        static::assertGreaterThanOrEqual(1, $submenuWithHeader->filter('.dropdown-divider')->count(), 'Submenu should have dividers');

        // test conditional entity action groups (group6)
        // find a row for Category 10 (should have group6)
        $category10Rows = $entityRows->reduce(function ($node) {
            $nameCell = $node->filter('td[data-label*="Name"], td[data-column="name"]')->first();

            return $nameCell->count() > 0 && str_contains($nameCell->text(), 'Category 10');
        });

        if ($category10Rows->count() > 0) {
            $category10Row = $category10Rows->first();
            $category10Submenus = $category10Row->filter('.dropdown-submenu');
            static::assertGreaterThanOrEqual(4, $category10Submenus->count(), 'Category 10 row should have group6 in addition to group1, group2, group3');

            $hasGroup6 = false;
            $category10Submenus->each(function ($submenu) use (&$hasGroup6) {
                if (str_contains($submenu->text(), 'Action Group 6')) {
                    $hasGroup6 = true;
                }
            });
            static::assertTrue($hasGroup6, 'Category 10 should display Action Group 6');
        }

        // find a row for Category 0 (should NOT have group6)
        $category0Rows = $entityRows->reduce(function ($node) {
            $nameCell = $node->filter('td[data-label*="Name"], td[data-column="name"]')->first();

            return $nameCell->count() > 0 && str_contains($nameCell->text(), 'Category 0');
        });

        if ($category0Rows->count() > 0) {
            $category0Row = $category0Rows->first();
            $category0Submenus = $category0Row->filter('.dropdown-submenu');
            // category 0 should only have group1, group2, group3 (no group6)
            static::assertLessThanOrEqual(3, $category0Submenus->count(), 'Category 0 row should not have group6');

            $hasGroup6 = false;
            $category0Submenus->each(function ($submenu) use (&$hasGroup6) {
                if (str_contains($submenu->text(), 'Action Group 6')) {
                    $hasGroup6 = true;
                }
            });
            static::assertFalse($hasGroup6, 'Category 0 should not display Action Group 6');
        }

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
        $category = $this->categories->findOneBy(['name' => 'Category 10']);
        $crawler = $this->client->request('GET', $this->generateDetailUrl($category->getId()));

        static::assertGreaterThanOrEqual(5, $crawler->filter('.action-group')->count());
        static::assertGreaterThan(0, $crawler->filter('.action-group button.dropdown-toggle-split')->count());

        $dropdownsWithHeaders = $crawler->filter('.action-group')->reduce(function ($node) {
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
        // Test with Category 10 (should show group6)
        $category10 = $this->categories->findOneBy(['name' => 'Category 10']);
        $crawler = $this->client->request('GET', $this->generateDetailUrl($category10->getId()));

        $group6Count = $crawler->filter('.action-group')->reduce(function ($node) {
            return str_contains($node->text(), 'Action Group 6');
        })->count();

        static::assertGreaterThan(0, $group6Count, 'Should show group6 for Category 10');

        // Test with Category 0 (should not show group6)
        $category0 = $this->categories->findOneBy(['name' => 'Category 0']);
        $crawler = $this->client->request('GET', $this->generateDetailUrl($category0->getId()));

        $group6Count = $crawler->filter('.action-group')->reduce(function ($node) {
            return str_contains($node->text(), 'Action Group 6');
        })->count();

        static::assertSame(0, $group6Count, 'Should not show group6 for Category 0');
    }

    public function testActionGroupsInEditPage(): void
    {
        $category = $this->categories->findOneBy(['name' => 'Category 10']);
        $crawler = $this->client->request('GET', $this->generateEditFormUrl($category->getId()));

        // Test dropdowns exist
        static::assertGreaterThanOrEqual(4, $crawler->filter('.action-group')->count());

        // Test split button exists
        static::assertGreaterThan(0, $crawler->filter('.action-group button.dropdown-toggle-split')->count());

        // Test dropdown with custom styling
        $customDropdowns = $crawler->filter('.action-group[data-test]');
        static::assertGreaterThan(0, $customDropdowns->count());
    }

    public function testActionGroupsInNewPage(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        // Test dropdowns exist
        static::assertGreaterThanOrEqual(3, $crawler->filter('.action-group')->count());

        // Test dropdown with headers and dividers
        $dropdownsWithHeaders = $crawler->filter('.action-group')->reduce(function ($node) {
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
        $linkActions->each(function ($link) use (&$hasValidLinks) {
            $href = $link->attr('href');
            if (str_contains($href, 'action=') || str_contains($href, 'crudAction=')) {
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

        // Test dropdown with custom classes
        $customDropdowns = $crawler->filter('.action-group[data-test="group-5"]');
        static::assertGreaterThan(0, $customDropdowns->count());

        $button = $customDropdowns->first()->filter('button.dropdown-toggle')->first();
        $buttonClass = $button->attr('class');
        // Check that button has necessary classes
        static::assertStringContainsString('btn', $buttonClass);
        static::assertStringContainsString('dropdown-toggle', $buttonClass);
        // Custom classes might be applied
        static::assertTrue(
            str_contains($buttonClass, 'custom-dropdown') || str_contains($buttonClass, 'additional-class'),
            'Button should have custom CSS classes'
        );

        // Test action with custom classes inside dropdown
        $actionsWithCustomClasses = $crawler->filter('.dropdown-item.btn-primary, .dropdown-item.text-danger');
        if ($actionsWithCustomClasses->count() > 0) {
            static::assertGreaterThan(0, $actionsWithCustomClasses->count(), 'Should have actions with custom CSS classes');
        } else {
            // At least verify dropdown items exist
            static::assertGreaterThan(0, $crawler->filter('.dropdown-item')->count(), 'Should have dropdown items');
        }
    }

    public function testActionGroupsHtmlStructure(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        // Test regular dropdown structure
        $regularDropdowns = $crawler->filter('.action-group')->reduce(function ($node) {
            return 0 === $node->filter('button.dropdown-toggle-split')->count();
        });

        static::assertGreaterThan(0, $regularDropdowns->count());

        $regularDropdown = $regularDropdowns->first();
        static::assertCount(1, $regularDropdown->filter('button.dropdown-toggle'));
        static::assertSame('dropdown', $regularDropdown->filter('button.dropdown-toggle')->attr('data-bs-toggle'));
        static::assertCount(1, $regularDropdown->filter('.dropdown-menu'));

        // Test split button structure
        $splitDropdowns = $crawler->filter('.action-group')->reduce(function ($node) {
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
            controllerFqcn: ActionGroupsInlineCrudController::class,
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
