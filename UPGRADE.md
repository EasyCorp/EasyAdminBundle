UPGRADE FROM EASYADMIN 4.X to 5.X
=================================

## Pretty URLs

Using pretty URLs is now mandatory. They are created with a custom route loader
that must be enabled in your application. If you use Symfony Flex, this file is
created automatically for you. Otherwise, create this file manually:

```yaml
# config/routes/easyadmin.yaml
easyadmin:
    resource: .
    type: easyadmin.routes
```

## Admin Context

The global `ea` variable injected in all templates is removed in favor of the
equivalent `ea()` Twig function, which returns the current context of the
EasyAdmin application:

```php
// Before (4.x)
{{ ea.i18n.translationDomain }}

// After (5.x)
{{ ea().i18n.translationDomain }}
```

## Main Menus

The `linkToCrud()` method used to link to CRUD controllers from the main menu of the
dashboard was removed in favor of the new `linkTo()` method:

```php
// Before (4.x)
yield MenuItem::linkToCrud('Categories', 'fa fa-tags', Category::class);
yield MenuItem::linkToCrud('Blog Posts', 'fa fa-file-text', BlogPost::class);
yield MenuItem::linkToCrud(null, null, Comment::class);

// After (5.x)
yield MenuItem::linkTo(CategoryCrudController::class, 'Categories', 'fa fa-tags');
yield MenuItem::linkTo(BlogPostCrudController::class, 'Blog Posts', 'fa fa-file-text');
yield MenuItem::linkTo(CommentCrudController::class);
```

## Actions

Some methods related to actions have been removed in favor of equivalent
methods with better names:

```php
// Before (4.x)
$action->displayAsLink()->...
$action->displayAsButton()->...
$action->displayAsForm()->...

// After (5.x)
$action->renderAsLink()->...
$action->renderAsButton()->...
$action->renderAsForm()->...
```

## Referrers

EasyAdmin URLs no longer include the `referrer` query parameter, and the
`AdminContext:getReferrer()` method was removed.

The `referrerUrl` property and the `getReferrerUrl()` method of `BatchActionDto`
were removed. The referrer URL is now handled automatically inside EasyAdmin.

In your own actions, you can redirect to a specific URL (built with the
`AdminUrlGenerator`) or get the referrer URL from the HTTP headers provided by browsers:

```php
// Before (4.x)
return $this->redirect($context->getReferrer());
return $this->redirect($batchActionDto->getReferrer());

// After (5.x)
return $this->redirect($adminContext->getRequest()->headers->get('referer'));
```

## Forms

Form panels are now called Form fieldsets and the `FormField::addPanel()` method
was removed:

```php
// Before (4.x)
yield FormField::addPanel('...');

// After (5.x)
yield FormField::addFieldset('...');
```

## Contracts

The following contract interfaces changed:

### `Contracts\Context\AdminContextInterface`

```php
// Before (4.x)
public function getCrudControllers(): CrudControllerRegistry;

// After (5.x)
public function getAdminControllers(): AdminControllerRegistry;
```

The `getSignedUrls()` and `getReferrer()` methods are removed.

### `Contracts\Controller\CrudControllerInterface`

```php
// Before (4.x)
public function createEntity(string $entityFqcn);

// After (5.x)
public function createEntity(string $entityFqcn): object;
```

### `Contracts\Orm\EntityPaginatorInterface`

```php
// Before (4.x)
public function getResultsAsJson(): string;

// After (5.x)
public function getResultsAsJson(?callable $callback = null, ?string $twigTemplate = null, bool $renderAsHtml = false): string;
```

### `Contracts\Provider\AdminContextInterface`

```php
// Before (4.x)
public function hasContext(): bool;

// After (5.x)
// this method no longer exists;
// alternative: check if getContext() return value is null
```

### `Contracts\Menu\MenuItemMatcherInterface`

The `isSelected()` and `isExpanded()` methods were removed. A new
`markSelectedMenuItem(array<MenuItemDto> $menuItems, Request $request)` method
has been added.

### `Contracts\Router\AdminRouteGeneratorInterface`

```php
// Before (4.x)
public function findRouteName(string $dashboardFqcn, string $crudControllerFqcn, string $actionName): ?string;

// After (5.x)
public function findRouteName(string|null $dashboardFqcn = null, string|null $crudControllerFqcn = null, string|null $actionName = null): ?string;
```

The `usesPrettyUrls()` method was removed.

## Static Analysis

In 5.x, PHPStan will report an error if a class extends
`AbstractCrudController` without specifying the entity type:

> Class App\Controller\Admin\UserCrudController extends generic class
> EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController
> but does not specify its types: TEntity

To fix this, update your controller like this:

```diff
+ /**
+  * @extends AbstractCrudController<User>
+  */
  class UserCrudController extends AbstractCrudController
  {
```
