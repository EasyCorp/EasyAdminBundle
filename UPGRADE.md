Upgrade Guide
=============

## EasyAdmin 5.7.0

### The `ea` Global Twig Variable Is Removed

The 5.0 upgrade notes already said that the global `ea` Twig variable was removed,
but it was still registered by mistake (and it holds the `AdminContextProvider` service
nstead of the actual admin context). It's now removed for real. Use the `ea()` Twig
function instead.

If a form theme extends an EasyAdmin template, call `ea()` directly inside the
`{% extends %}` tag, because Symfony resolves the parent templates of form themes
before running any `{% set %}` tag of the template:

```twig
{# before #}
{% set ea = ea() %}
{% extends ea.templatePath('crud/edit') %}

{# after #}
{% extends ea().templatePath('crud/edit') %}
```

## EasyAdmin 5.4.0

### Detail Page Templates Are Now Overridable Individually

The Twig macros previously defined in the `@EasyAdmin/crud/detail.html.twig`
template (`render_field_contents`, `render_layout_field`, `render_tab_*`,
`render_column_*` and `render_fieldset_*`) have been extracted into standalone
templates under `@EasyAdmin/crud/detail/` and removed from `detail.html.twig`.

This lets you override how a specific element of the detail page renders
(e.g. only the fieldsets) without copying the whole `detail.html.twig` template.
The new template names, usable with `overrideTemplate()`/`overrideTemplates()`
or via the `templates/bundles/EasyAdminBundle/` directory, are:
`crud/detail/field_group`, `crud/detail/layout_field`, `crud/detail/tab_list`,
`crud/detail/tab_group_open`, `crud/detail/tab_group_close`,
`crud/detail/tab_open`, `crud/detail/tab_close`,
`crud/detail/column_group_open`, `crud/detail/column_group_close`,
`crud/detail/column_open`, `crud/detail/column_close`,
`crud/detail/fieldset_open` and `crud/detail/fieldset_close`.

Applications that copied the whole `detail.html.twig` template or that extend it
with `@!EasyAdmin/crud/detail.html.twig` are not affected. In the unlikely case
that your application imported those macros directly with
`{% from '@EasyAdmin/crud/detail.html.twig' import ... %}`, use the new
templates instead: `{{ include(ea().templatePath('crud/detail/...'), {field: field}, with_context: false) }}`.

## EasyAdmin 5.3.1

### Removed the Entity-to-Controller Cache Entry

The `easyadmin.crud.entity_fqcn_to_controller_fqcn` cache entry and its
associated `CacheKey::ENTITY_FQCN_TO_CRUD_FQCN` public constant have been
removed. This map of entity FQCNs to their CRUD controllers was an internal
feature mostly needed by previous EasyAdmin versions; in EasyAdmin 5 it was
written to the cache but never read, because the bundle now derives the
entity-to-controller map from the reverse `CacheKey::CRUD_FQCN_TO_ENTITY_FQCN`
cache entry. If your application read this cache entry directly,
apply `array_flip()` to the contents of the remaining cache entry instead.

## EasyAdmin 5.3.0

### CSS Cascade Layers

All CSS styles of the backend are now assigned to [cascade layers](https://developer.mozilla.org/en-US/docs/Web/CSS/@layer):
`vendor` (Bootstrap, Font Awesome and other third-party styles), `ea` (the
EasyAdmin styles, with the `ea.tokens`, `ea.base`, `ea.components` and
`ea.utilities` sublayers) and `ea-overrides` (a few special rules explained below).

Since unlayered CSS always wins over layered CSS, any custom styles you add
(e.g. with `addCssFile()`) now override the backend styles regardless of their
specificity or loading order. In practice this means:

* Most custom styles keep working as before, and overriding backend styles no
  longer requires `!important` or artificially specific selectors; you can
  safely remove those workarounds.
* If some global CSS of your application (e.g. a reset stylesheet or generic
  rules like `button { ... }`) is loaded in the backend pages, it previously
  lost against the more specific backend styles but now it wins. If the backend
  looks different after upgrading, scope those global styles or assign them to
  their own cascade layer.
* For `!important` declarations the layer order is inverted, so the few
  `!important` rules that the backend still needs (defined in the `ea-overrides`
  layer) now win over `!important` declarations in your unlayered custom CSS.
  If one of your `!important` overrides stopped working, remove the
  `!important` flag: the normal declaration now wins.

## EasyAdmin 5.2.0

### HTML Markup Changes

The HTML markup of the boolean switch has changed. Before, the element wrapping
the switch applied the `.form-switch` CSS class and the checkbox used the
`.form-check-input` class. Now, the switch is rendered with the new
`<twig:ea:Switch>` component: the wrapping element uses the `.ea-switch` class and
the checkbox uses the `.ea-switch-input` class. This affects both the switch shown
in the `index` page and the one displayed in the `edit`/`new` forms. Update any
custom CSS or JavaScript that targeted the old selectors.

The HTML markup of the `<twig:ea:Flag>` component has changed slightly. There's a
new `<span class="country-flag-wrapper">` element that wraps the `<svg>` image
and the optional country name text. If your custom CSS or JavaScript selects
country flags with direct-child or sibling selectors (e.g. `td > svg.country-flag`),
update them; descendant selectors (e.g. `td svg.country-flag`) keep working as before.

The HTML of icons when using a custom icon set has changed. Before, all HTML attributes
were wrongly applied to both the wrapping `<span>` element and the inner `<svg>` element.
Now, HTML attributes are only applied to the wrapping element. Update any custom CSS
or JavaScript that targeted those attributes on the inner `<svg>` element.

## Upgrading from Symfony 4.x to 5.x

### Pretty URLs

Using pretty URLs is now mandatory. They are created with a custom route loader
that must be enabled in your application. If you use Symfony Flex, this file is
created automatically for you. Otherwise, create this file manually:

```yaml
# config/routes/easyadmin.yaml
easyadmin:
    resource: .
    type: easyadmin.routes
```

### Admin Context

The global `ea` variable injected in all templates is removed in favor of the
equivalent `ea()` Twig function, which returns the current context of the
EasyAdmin application:

```php
// Before (4.x)
{{ ea.i18n.translationDomain }}

// After (5.x)
{{ ea().i18n.translationDomain }}
```

### Main Menus

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

### Custom CRUD Actions

Custom CRUD actions now require to apply the `#[AdminRoute]` attribute to them.
Otherwise, they are ignored when generating routes for the backend and code
like `->linkToCrudAction('foo')` will no longer work:

```php
// Before (4.x)
use App\Entity\Comment;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use Symfony\Component\HttpFoundation\Response;

class CommentCrudController extends AbstractCrudController
{
    // ...

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(
                Crud::PAGE_INDEX,
                Action::new('markSpam', 'action.mark_spam')->linkToCrudAction('markCommentAsSpam')
            )
        ;
    }

    public function markCommentAsSpam(AdminContext $context): Response
    {
        /** @var Comment $comment */
        $comment = $context->getEntity()->getInstance();

        $comment->markAsSpam();
        $this->entityManager->flush();

        return $this->redirectToRoute('admin_comment_index');
    }
}

// After (5.x)
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
// ...

class CommentCrudController extends AbstractCrudController
{
    // ...
    
    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(
                Crud::PAGE_INDEX,
                Action::new('markSpam', 'action.mark_spam')->linkToCrudAction('markCommentAsSpam')
            )
        ;
    }

    #[AdminRoute('/{entityId:comment.id}/mark-as-spam')]
    public function markCommentAsSpam(Comment $comment): Response
    {
        $comment->markAsSpam();
        $this->entityManager->flush();

        return $this->redirectToRoute('admin_comment_index');
    }
}
```

### Actions

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

### Referrers

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

### Forms

Form panels are now called Form fieldsets and the `FormField::addPanel()` method
was removed:

```php
// Before (4.x)
yield FormField::addPanel('...');

// After (5.x)
yield FormField::addFieldset('...');
```

### Attributes

The `#[AdminCrud]` and `#[AdminAction]` attributes have been removed in favor
of the `#[AdminRoute]` attribute.

### Contracts

The following contract interfaces changed:

#### `Contracts\Context\AdminContextInterface`

```php
// Before (4.x)
public function getCrudControllers(): CrudControllerRegistry;

// After (5.x)
public function getAdminControllers(): AdminControllerRegistry;
```

The `getSignedUrls()` and `getReferrer()` methods are removed.

#### `Contracts\Controller\CrudControllerInterface`

```php
// Before (4.x)
public function createEntity(string $entityFqcn);

// After (5.x)
public function createEntity(string $entityFqcn): object;
```

#### `Contracts\Orm\EntityPaginatorInterface`

```php
// Before (4.x)
public function getResultsAsJson(): string;

// After (5.x)
public function getResultsAsJson(?callable $callback = null, ?string $twigTemplate = null, bool $renderAsHtml = false): string;
```

#### `Contracts\Provider\AdminContextInterface`

```php
// Before (4.x)
public function hasContext(): bool;

// After (5.x)
// this method no longer exists;
// alternative: check if getContext() return value is null
```

#### `Contracts\Menu\MenuItemMatcherInterface`

The `isSelected()` and `isExpanded()` methods were removed. A new
`markSelectedMenuItem(array<MenuItemDto> $menuItems, Request $request)` method
has been added.

#### `Contracts\Router\AdminRouteGeneratorInterface`

```php
// Before (4.x)
public function findRouteName(string $dashboardFqcn, string $crudControllerFqcn, string $actionName): ?string;

// After (5.x)
public function findRouteName(string|null $dashboardFqcn = null, string|null $crudControllerFqcn = null, string|null $actionName = null): ?string;
```

The `usesPrettyUrls()` method was removed.

### Static Analysis

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
