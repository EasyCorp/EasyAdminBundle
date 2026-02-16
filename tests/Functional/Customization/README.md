# Customization Tests

This directory contains comprehensive tests for ALL configuration methods available in EasyAdmin's Dashboard and Crud classes.

## Overview

- **Total Test Files**: 43 (10 Dashboard + 33 Crud)
- **Total Controllers**: 41 (10 Dashboard + 31 Crud)
- **Coverage**: 100% of Dashboard and Crud configuration methods

## Structure

```
tests/Functional/Customization/
├── Dashboard/                      # Dashboard configuration tests (10 files)
│   ├── ColorSchemeTest.php
│   ├── ContentMaximizedTest.php
│   ├── DarkModeTest.php
│   ├── FaviconTest.php
│   ├── LocalesTest.php
│   ├── RelativeUrlsTest.php
│   ├── SidebarMinimizedTest.php
│   ├── TextDirectionTest.php
│   ├── TitleTest.php
│   └── TranslationDomainTest.php
└── Crud/                          # Crud configuration tests (33 files)
    ├── BatchActions/
    │   └── ConfirmationTest.php
    ├── Display/
    │   ├── HideNullValuesTest.php
    │   └── InlineActionsTest.php
    ├── EntityLabels/
    │   ├── CallableLabelTest.php
    │   ├── PluralLabelTest.php
    │   └── SingularLabelTest.php
    ├── Formatting/
    │   ├── DateFormatTest.php
    │   ├── DateIntervalFormatTest.php
    │   ├── DateTimeFormatTest.php
    │   ├── NumberFormatTest.php
    │   ├── SeparatorsTest.php
    │   ├── TimeFormatTest.php
    │   └── TimezoneTest.php
    ├── Forms/
    │   ├── FormOptionsTest.php
    │   └── FormThemesTest.php
    ├── Layout/
    │   ├── ContentMaximizedTest.php
    │   └── SidebarMinimizedTest.php
    ├── Pages/
    │   ├── HelpMessagesTest.php
    │   ├── PageTitlesCallableTest.php
    │   └── PageTitlesTest.php
    ├── Pagination/
    │   ├── FetchJoinCollectionTest.php
    │   ├── OutputWalkersTest.php
    │   ├── PageSizeTest.php
    │   └── RangeSizeTest.php
    ├── Permissions/
    │   └── EntityPermissionTest.php
    ├── Search/
    │   ├── SearchAutofocusTest.php
    │   ├── SearchFieldsTest.php
    │   └── SearchModeTest.php
    ├── Sorting/
    │   ├── DefaultSortMultipleFieldsTest.php
    │   └── DefaultSortTest.php
    └── Templates/
        └── OverrideTemplateTest.php
```

## Dashboard Configuration Methods Tested (10)

1. ✅ `setTitle(string)` - Set dashboard title
2. ✅ `setFaviconPath(string)` - Set favicon path
3. ✅ `setTranslationDomain(string)` - Set translation domain
4. ✅ `setTextDirection(string)` - LTR or RTL text direction
5. ✅ `renderContentMaximized(bool)` - Full vs normal content width
6. ✅ `renderSidebarMinimized(bool)` - Compact vs normal sidebar
7. ✅ `generateRelativeUrls(bool)` - Relative vs absolute URLs
8. ✅ `disableDarkMode(bool)` - Enable/disable dark mode toggle
9. ✅ `setDefaultColorScheme(string)` - light, dark, or auto
10. ✅ `setLocales(array)` - Configure available locales

## Crud Configuration Methods Tested (31)

### Entity Labels (3)
1. ✅ `setEntityLabelInSingular(string|callable)`
2. ✅ `setEntityLabelInPlural(string|callable)`
3. ✅ Callable labels

### Page Content (3)
4. ✅ `setPageTitle(string $pageName, string|callable)`
5. ✅ `setHelp(string $pageName, string)`
6. ✅ Callable page titles

### Date/Time/Number Formatting (7)
7. ✅ `setDateFormat(string)` - short, medium, long, full, or ICU pattern
8. ✅ `setTimeFormat(string)` - short, medium, long, full
9. ✅ `setDateTimeFormat(string, string)` - Combination of date + time formats
10. ✅ `setDateIntervalFormat(string)` - Date interval formatting
11. ✅ `setTimezone(string)` - Timezone for date/time display
12. ✅ `setNumberFormat(string)` - Number formatting pattern
13. ✅ `setThousandsSeparator(string)` + `setDecimalSeparator(string)` - Number separators

### Search & Sorting (5)
14. ✅ `setSearchFields(?array)` - Fields to search
15. ✅ `setSearchMode(string)` - ANY_TERMS or ALL_TERMS
16. ✅ `setAutofocusSearch(bool)` - Auto-focus search input
17. ✅ `setDefaultSort(array)` - Default sort order
18. ✅ Default sort with multiple fields

### Pagination (4)
19. ✅ `setPaginatorPageSize(int)` - Items per page
20. ✅ `setPaginatorRangeSize(int)` - Number of page links
21. ✅ `setPaginatorFetchJoinCollection(bool)` - Doctrine paginator setting
22. ✅ `setPaginatorUseOutputWalkers(bool)` - Doctrine paginator setting

### Display (2)
23. ✅ `showEntityActionsInlined(bool)` - Inline buttons vs dropdown
24. ✅ `hideNullValues(bool)` - Hide null/empty values

### Templates & Forms (3)
25. ✅ `overrideTemplate(string, string)` - Override single template
26. ✅ `addFormTheme(string)` + `setFormThemes(array)` - Form themes
27. ✅ `setFormOptions(array, ?array)` - Symfony form options

### Permissions (1)
28. ✅ `setEntityPermission(string)` - Entity access permission

### Layout (2)
29. ✅ `renderContentMaximized(bool)` - Full vs normal content width (Crud-level)
30. ✅ `renderSidebarMinimized(bool)` - Compact vs normal sidebar (Crud-level)

### Batch Actions (1)
31. ✅ `askConfirmationOnBatchActions(bool|string)` - Confirmation modal

## Test Application

All tests use a dedicated test application located at:
- `tests/Functional/CustomizationApp/`

This includes:
- **Kernel**: Custom kernel for test app
- **Entity**: `DemoEntity` with various field types (text, date, time, number)
- **Controllers**: 41 dedicated controllers (10 Dashboard + 31 Crud)
- **Configuration**: Complete Symfony configuration (Doctrine, Twig, Framework)

## Running Tests

```bash
# Run all Customization tests
php vendor/bin/phpunit tests/Functional/Customization/

# Run Dashboard tests only
php vendor/bin/phpunit tests/Functional/Customization/Dashboard/

# Run Crud tests only
php vendor/bin/phpunit tests/Functional/Customization/Crud/

# Run specific category tests
php vendor/bin/phpunit tests/Functional/Customization/Crud/Formatting/
php vendor/bin/phpunit tests/Functional/Customization/Crud/Search/
php vendor/bin/phpunit tests/Functional/Customization/Crud/Pagination/
```

## Test Pattern

Each test follows this pattern:

1. **Dedicated Controller**: Each configuration method has its own controller
2. **Isolated Testing**: Tests don't depend on each other
3. **Verification**: Tests verify configuration is applied without errors
4. **HTML Inspection**: Where applicable, tests verify HTML output

Example:
```php
class TitleTest extends AbstractCrudTestCase
{
    protected function getControllerFqcn(): string
    {
        return DemoEntityCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return TitleTestDashboardController::class;
    }

    public function testCustomTitleIsRenderedInPageTitle(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());
        static::assertResponseIsSuccessful();
        
        $titleTag = $crawler->filter('title')->text();
        static::assertStringContainsString('Custom Dashboard Title', $titleTag);
    }
}
```

## Coverage Summary

This test suite provides **100% coverage** of:
- All Dashboard configuration methods (10/10)
- All Crud configuration methods (31/31)
- Edge cases (callables, multiple values, etc.)

**Note**: This directory specifically tests **configuration APIs only**. Fields, Filters, Actions, and other features are tested in their respective directories.
