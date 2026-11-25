Upgrade Guide
=============

This guide is for humans. If you are an AI agent, read [UPGRADE-AI.md](UPGRADE-AI.md) instead.

## EasyAdmin 5.10.5

### Route Requirements and Defaults of a Class Are No Longer Replaced by the Ones of Its Actions

When both the `#[AdminRoute]` attribute of a controller class and the one of an
action defined `requirements`, `defaults` or `options`, the values of the action
replaced the ones of the class entirely. The route still contained the placeholders
of the class path, but without the requirements and defaults that the class defined
for them. Now they're merged key by key, like the `#[Route]` attribute of Symfony
does, and the values of the action win for the keys defined in both.

If an action relied on this to remove a requirement or a default of its class,
redefine that key in the attribute of the action:

```php
#[AdminRoute(path: '/report/{kind}', name: 'report', options: ['requirements' => ['kind' => 'a|b']])]
class ReportController
{
    // before: '{kind}' accepted any value in this route
    #[AdminRoute(path: '/{id}', name: 'show', options: ['requirements' => ['id' => '\d+']])]

    // after, to keep accepting any value
    #[AdminRoute(path: '/{id}', name: 'show', options: ['requirements' => ['id' => '\d+', 'kind' => '[^/]+']])]
}
```

## EasyAdmin 5.10.4

### Autocomplete Checks the Field and the Page of Its Context

The autocomplete of `AssociationField` now checks that the user can access the
page and the field that it was requested from:

* if the callable of `setQueryBuilder()` returns a query builder for an entity
  other than the related entity of the field, the autocomplete returns a 403
  error. Modify and return the query builder passed to the callable instead;
* the autocomplete fields inside embedded CRUD forms (`renderAsEmbeddedForm()`
  and `useEntryCrudForm()`) require access to the `new` or `edit` action of
  the embedded CRUD controller in the current dashboard;
* the entity permission (`setEntityPermission()`) of the CRUD controller that
  defines the field is checked for the `detail`, `edit` and `new` pages. When
  there's no entity (the `new` page and the autocomplete fields of embedded
  CRUD forms), it's checked with a `null` subject, so voters and expressions
  used as entity permission must handle `null`.

### The Permission of Form Tabs, Columns and Fieldsets Now Applies to Their Fields

A permission set with `setPermission()` on a form tab, column or fieldset
(`FormField::addTab()`, `addColumn()` and `addFieldset()`) now also applies to
all the fields defined inside it, in all pages (including `index`). Before, only
the tab, column or fieldset was hidden and its fields were displayed inside the
previous one.

If you used `setPermission()` on a tab, column or fieldset only to hide its
title, move the fields that all users must see out of it.

### `EntityRepository` Needs an Authorization Checker

The search, the sort and the filters of the `index` page now ignore the
properties of the fields that the user can't see. Not passing an instance of
`AuthorizationCheckerInterface` as the sixth argument of the constructor of
`EasyCorp\Bundle\EasyAdminBundle\Orm\EntityRepository` is deprecated. This only
affects you if you create that service yourself.

### Parameters of `linkToRoute()` That Aren't Part of the Route

When an action or menu item uses `linkToRoute()` to link to a regular Symfony
route, the controller of that route now only gets the arguments defined by the
route (its placeholders and defaults), like when browsing the route URL. The
other parameters passed to `linkToRoute()` are no longer request attributes or
controller arguments. Add them as placeholders of the route, or read them from
the query string with `$request->query->all('routeParams')`.

### The Entity ID of Admin URLs Wins Over the `id` Parameter

On routes that use the `{id}` placeholder instead of `{entityId}`, the entity
ID set with `setEntityId()` (or taken from the current `detail` or `edit` page)
now always wins over an `id` parameter. If you used `->set('id', ...)` to point
an admin URL to another entity, use `->setEntityId(...)` instead.

### Flysystem Files Are Resolved Inside the Upload Dir

With `setFlysystemStorage()`, the stored value of file and image fields is now
always looked up, displayed and deleted inside the `setUploadDir()` directory of
the storage, as with the local storage. If your entities store paths that already
include the upload dir, remove that prefix from the stored values or use
`setUploadDir('/')`. Custom callables set in the `upload_delete` form type option
now get a `FlysystemFile` whose `getPathname()` includes the upload dir.

### File and Image Fields Force the Download of More File Types

File and image fields now force the download of all uploaded files except raster
images (`.jpg`, `.png`, `.gif`, `.webp`, `.bmp`, `.ico`), PDFs, `.txt` files
and `.mp3`, `.ogg`, `.mp4` and `.webm` files. Call `allowRiskyInlineRender()` on
the field to keep the previous behavior if you trust the uploaded files.

The URLs of the files now encode the `?` and `#` characters of the stored file
names. If you store relative paths with a query string on purpose, store absolute
URLs instead.

## EasyAdmin 5.10.2

### `allowedDashboards` and `deniedDashboards` Are Now Applied to CRUD Routes

The `allowedDashboards` and `deniedDashboards` options of `#[AdminRoute]` were
ignored in CRUD controllers and their actions, so their routes were generated in
every dashboard. Now they're applied: the options of the class attribute apply to
all the routes of the CRUD controller (including the built-in actions) and the
options of an action attribute override them.

If your CRUD controllers or their actions use these options, check that they list
the right dashboards. In the excluded dashboards, their URLs return a 404 error,
their menu items point to the dashboard and links that use their route names fail.

### Dashboard Route Options Are Now Applied to All the Dashboard Routes

The `requirements`, `host`, `schemes` and `condition` defined in the `routeOptions`
argument of `#[AdminDashboard]` were only applied to the route of the dashboard
`index()`. Now they're also applied to all the other routes of the dashboard (the
routes of its CRUD controllers and of the `#[AdminRoute]` attributes). The
requirements, host and schemes of an `#[AdminRoute]` attribute win over the ones of
the dashboard, and its condition is combined with the dashboard condition using `and`.
The other options (e.g. `methods`) still apply only to the dashboard route.

If your dashboards use these options, check that all their routes must have the same
restrictions, because the requests that don't match them now return a 404 error. For
example, a dashboard `condition` that only allows GET requests now also blocks the
forms of the backend. Use the `methods` option to restrict the HTTP methods of the
dashboard route.

### `setPermissions()` No Longer Removes the Permissions of Other Actions

The `setPermissions()` method of `Actions` replaced all the action permissions
defined before calling it. For example, a CRUD controller that called
`setPermissions([Action::NEW => 'ROLE_ADMIN'])` removed the permission that the
dashboard defined for `Action::DELETE`.

Now `setPermissions()` works like calling `setPermission()` for each action: it
only adds or changes the permissions of the given actions. Calling it with an
empty array changes nothing.

If you used `setPermissions()` to remove the permissions defined before (in the
dashboard, in a parent CRUD controller or in an actions extension), set the
permission of those actions to an empty string:

```php
public function configureActions(Actions $actions): Actions
{
    return $actions->setPermissions([
        Action::NEW => 'ROLE_ADMIN',
        // the dashboard restricts this action, but this CRUD controller doesn't
        Action::DELETE => '',
    ]);
}
```

### Nested CRUD Forms Apply the Entity Permission of Their Controller

The CRUD forms nested in other forms (`AssociationField::renderAsEmbeddedForm()`
and the entries of `CollectionField`) now apply the `setEntityPermission()` option
of their CRUD controller, as the pages of that controller do:

* if the user can't access the associated entity, or any of the entries of the
  collection, the field is removed from the form and its property doesn't change
  when submitting the form;
* if the entity permission denies new entities (it's checked with a `null`
  subject, as in the `new` page), the collection doesn't allow adding entries and
  the embedded form of an empty association is removed.

If the entity permission is checked by a voter that only supports entity instances,
it now denies new entries to all users (as the `new` page of that controller already
did). If users must create those entities in the nested form, make the voter also
vote when the subject is `null`:

```php
protected function supports(string $attribute, mixed $subject): bool
{
    return self::EDIT === $attribute && (null === $subject || $subject instanceof Address);
}
```

### The Default Field Templates Escape the Contents of Fields

The `crud/field/text` template is the default template of all fields, and it printed
their contents without escaping them. Only `TextField` and `TextareaField` escaped
their contents before passing them to the template. The contents of the other fields
that use this template (fields defined as a string or with `Field::new()` for
unmapped or nested properties like `'fullName'` or `'author.name'`, `SlugField` and
custom fields) were rendered as HTML.

Now the `crud/field/text`, `crud/field/textarea` and `crud/field/choice` templates
escape the contents, unless the field configurator marks them as safe with the
`FieldDto::OPTION_FORMATTED_VALUE_IS_SAFE_HTML` custom option. `TextField`,
`TextareaField`, `ChoiceField` and the strings returned by `formatValue()` are
rendered as before.

If one of those fields must render HTML contents, use a `TextField` with the
`renderAsHtml()` option or return the HTML contents in `formatValue()`:

```php
// before
yield Field::new('statusBadge');

// after
yield TextField::new('statusBadge')->renderAsHtml();
```

If your application overrides the `crud/field/text`, `crud/field/textarea` or
`crud/field/choice` templates and prints `field.formattedValue|raw`, apply the
`raw` filter only when `field.customOptions.get('formattedValueIsSafeHtml')` is
`true`. If a custom field configurator sets HTML contents as the formatted value of
a field rendered with those templates, set that custom option to `true` too.

### The Values of the Entity Placeholders Are Escaped

Page titles, entity labels and action labels are rendered as HTML. Now EasyAdmin
escapes the values of the `%entity_as_string%`, `%entity_id%` and `%entity_short_id%`
placeholders used in them, because they come from the entity data. Also,
`%entity_id%` and `%entity_short_id%` are now the ID of the loaded entity instead of
the ID typed in the URL.

If the `__toString()` method of an entity returns HTML on purpose and you show it
with `%entity_as_string%`, it's now displayed as text. Use a closure instead and
return the HTML from it:

```php
// before
$crud->setPageTitle(Crud::PAGE_DETAIL, '%entity_as_string%');

// after
$crud->setPageTitle(Crud::PAGE_DETAIL, static fn (Product $product): string => (string) $product);
```

The `translationParameters` of `ea().i18n` contain the escaped values. If a custom
template translates a message with these parameters and doesn't print it with the
`raw` filter, the values are escaped twice (`O'Neil` is shown as `O&#039;Neil`).
Remove these placeholders from that message and pass the value as your own parameter:

```twig
{# before #}
{{ 'Delete %entity_as_string%?'|trans(ea().i18n.translationParameters) }}

{# after #}
{{ 'Delete %name%?'|trans({'%name%': entity.instance.name}) }}
```

### Entity Labels of Expanded Choice Fields Are Now Escaped

When an `AssociationField` (or any field that uses Symfony's `EntityType`) sets
the `expanded` form option, the labels of its checkboxes and radio buttons
(the result of `__toString()` or of the `choice_label` option of the related
entities) were rendered as HTML. Now they are escaped, like in the dropdown
widget. The label of the field itself can still contain HTML.

If those labels must render HTML that you trust, call `renderAsHtml()` on the
`AssociationField`:

```php
yield AssociationField::new('categories')
    ->setFormTypeOption('expanded', true)
    ->renderAsHtml();
```

## EasyAdmin 5.10.1

### Strings Are No Longer Called as PHP Functions

Some options accept a string or a callable. A string that matched the name of a
PHP function (e.g. a page title like `'Time'` or `'Trim'`) was called as that
function. Now strings are never called in these options:

* `Crud::setPageTitle()`: the string is always the title;
* `Action::linkToUrl()`: the string is always the URL;
* `FormField::addTab()->setBadge()`: the string is always the badge content;
* `CollectionField::setEntryToStringMethod()`: the string is always the name of
  a method of the entry;
* `FileField::setUploadedFileNamePattern()`, `ImageField::setUploadedFileNamePattern()`
  and the `upload_filename` option of `FileUploadType`: the string is always the pattern.

If you passed a callable as a string (`'my_function'` or `'App\Util\Titles::build'`),
use a closure or the first-class callable syntax:

```php
// before
$crud->setPageTitle(Crud::PAGE_DETAIL, 'App\Util\Titles::build');

// after
$crud->setPageTitle(Crud::PAGE_DETAIL, Titles::build(...));
```

### `#[AdminRoute]` Options Are Now Applied to CRUD Routes

The `options` argument of `#[AdminRoute]` (`host`, `schemes`, `condition`,
`requirements`, `defaults`, etc.) was ignored in CRUD controllers, except for the
`methods` option of actions. Now those options are applied to the generated routes:
the options of the class attribute apply to all the routes of the CRUD controller,
and the options of an action attribute override them.

If your CRUD controllers or their actions already define those options, check that
they are correct, because the requests that don't match them now return a 404 error.
The `methods` option is still ignored in the attribute of the CRUD controller class;
define it in the attribute of each action.

### CSRF Tokens of Delete Actions and Boolean Switches Changed Their IDs

The CSRF tokens of the delete action and of the boolean switches of the index
page are now only valid for the CRUD controller (and the property) they were
generated for. Their IDs changed like this:

| Before      | After                                              |
| ----------- | -------------------------------------------------- |
| `ea-delete` | `ea-delete-<CRUD controller FQCN>`                 |
| `ea-toggle` | `ea-toggle-<CRUD controller FQCN>-<property name>` |

If your code checks those tokens (for example, in a `delete()` or `edit()`
method that doesn't call the parent method, or in a custom action that checks
the token of the confirmation modal), update it, because the tokens that
EasyAdmin generates no longer match the old IDs:

```php
// before
$this->isCsrfTokenValid('ea-delete', $token);

// after
$this->isCsrfTokenValid('ea-delete-'.$context->getCrud()->getControllerFqcn(), $token);
```

If your templates or your code generate those tokens (for example, if you
override the `delete_form` block or the
`@EasyAdmin/crud/includes/_action_confirmation_modal.html.twig` template),
tokens generated with the old IDs still work, but they are deprecated and they
will not be valid in EasyAdmin 6.0:

```twig
{# before #}
<input type="hidden" name="token" value="{{ csrf_token('ea-delete') }}" />

{# after #}
<input type="hidden" name="token" value="{{ csrf_token('ea-delete-' ~ ea().crud.controllerFqcn) }}" />
```

## EasyAdmin 5.10.0

### Flags Are Rounded and Outlined Everywhere

The `<twig:ea:Flag>` component now renders flags with rounded corners and a
subtle outline also outside EasyAdmin, because the styles are included in the
HTML of the flag. Use the new `radius` and `bordered` props to change them. To
render flags without any style:

```twig
<twig:ea:Flag countryCode="JP" radius="none" bordered="{{ false }}"/>
```

When the `showName` prop is enabled, the country name is now wrapped in a
`<span class="country-flag-name">` element.

The `--field-country-flag-border-color` CSS variable, which wasn't used, has
been removed. Use `--flag-outline-color` and `--flag-radius` to customize the
look of flags.

### Action Permissions Now Protect the URLs of Custom Actions

`Actions::setPermission()` and `Actions::disable()` used to only hide the buttons of
custom actions, so their URLs still worked. Now, running a custom action method
(used in `linkToCrudAction()`, or in `linkToRoute()` with a custom action route of
the same CRUD controller) throws a 403 error when the action is disabled or the user
doesn't have its permission.

Permission checks that you added inside those methods are no longer needed. If
several actions use the same method, one enabled and granted action is enough to run
it, so set the same permission on all of them or add `#[IsGranted]` to the method.
Actions that use `linkToUrl()` or other routes don't change: protect them with
`#[IsGranted]` or `access_control`.

### `?routeName=` URLs of Admin Routes Are Redirected

URLs like `/admin?routeName=admin_product_publish` now redirect (with a `307` status
code) to the URL of the admin route when `routeName` is an admin route or an alias of
one. EasyAdmin doesn't generate these URLs, so this only affects URLs written by hand.

### Batch Delete Checks All Its Permissions

Batch delete now checks the permission of `Action::BATCH_DELETE` and the entity
permission of each selected entity. If any of them isn't granted, no entity is
deleted.

### `AdminRouterSubscriber` Gets Its Services From a Service Locator

`AdminRouterSubscriber` now receives a single service locator in its constructor
instead of its services, so they are only built on EasyAdmin requests. Passing
the services as separate arguments still works, but it's deprecated and it will
not be possible in EasyAdmin 6.0. This only affects you if you create this
subscriber yourself.

## EasyAdmin 5.9.0

EasyAdmin UI has been refreshed. All elements remain the same on all pages,
but everything looks better and more modern now. The main visual changes:

* The default gray palette is now neutral instead of blueish
* The default accent color is black instead of indigo
* The default font is Inter instead of your default operating system font
* Alerts now have rounded corners and a space below them; flash messages too:
  they're still displayed above the page layout, but no longer as a full-width band
* In `detail` pages, field labels are now aligned to the start of the line, and boolean
  fields show the label before the value, like all other fields

You can't get the exact previous design back, but this gets you very close to it:

```php
namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\Font;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\GrayScale;
use EasyCorp\Bundle\EasyAdminBundle\Config\Theme;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    // ...

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            // ...
            ->setTheme(Theme::new()
                ->primaryColor('hsl(230, 61%, 58%)', dark: 'hsl(224, 84%, 49%)')
                ->grays(GrayScale::SLATE, dark: GrayScale::NEUTRAL)
                ->radius('xs')
                ->font(base: Font::System, monospace: Font::System)
            );
    }

    // ...
}
```

Other notable changes of the new design:

* The color palette (`--blue-600`, `--red-500`, etc.) now uses the values of
  Tailwind CSS 4 (in the `oklch()` format) instead of Tailwind CSS 3, so most
  colors are a bit more vivid. The chromatic colors also define a new `950` step.
* Custom pages now define a stylesheet that makes all elements look nice by default
  (font sizes, margins, etc.)
* Buttons now define more variants (`solid`, `outline`, `light`, `ghost` or `link`)
* `renderContentMaximized()` and `renderSidebarMinimized()` now apply on every page
  load. The widths selected by users with the layout buttons are only remembered in
  the pages where you don't call these methods
* Icons are now vertically centered with the text next to them, using any icon set and
  any font. The `<span class="icon">` element is now `inline-flex` and its `<svg>` is
  `display: block`, so the element is as tall as the icon and no longer as tall as a line
  of text. If your custom CSS moved icons a few pixels to fix their alignment (with
  `vertical-align`, `translate`, `position: relative`, etc.), remove those styles. If an
  element only contains an icon and its height came from that line of text, set its
  height explicitly.
* `Theme::spacing()` no longer scales buttons, inputs and switches freely, because they
  looked wrong when their padding changed but the size of their text didn't. They get one
  size smaller with values below `md`, and they don't get larger with values above it
* On small screens, the search input now takes the full width and the page actions are
  aligned to the start of the line. In the header of small screens, the user menu and the
  settings menu are now wrapped in a `<div class="responsive-header-actions">` element,
  so update any custom CSS that selected them as direct children of `.responsive-header`.
* The table of `index` pages is now wrapped in a `<div class="datagrid-scroll">` element,
  so wide tables scroll inside it instead of moving the whole page. Update any custom CSS
  that selected the table as a direct child of `.content-body`.

## EasyAdmin 5.8.0

### `createEntity()` Can Return `null`

The return type of `CrudControllerInterface::createEntity()` and
`AbstractCrudController::createEntity()` changed from `object` to `?object`
(`?TEntity` in PHPDoc). Return `null` to let the `new` form create the entity
with its `empty_data` option:

```php
// Before (5.0 - 5.7.0)
public function createEntity(string $entityFqcn): object;

// After (5.8.0)
public function createEntity(string $entityFqcn): ?object;
```

Overrides that declare `object` as their return type keep working. If your custom
actions call `$this->createEntity(...)`, static analysis tools now require you to
check for `null`. When `createEntity()` returns `null`, the entity instance is `null`
in the `new` page until the form is submitted, so all your callables that receive the
entity (e.g. in `ChoiceField::setChoices()` or `Action::linkToUrl()`) must accept `null`.

### The Color of Badges Is Now Defined With the `color` Prop

The `variant` prop of the `<twig:ea:Badge>` component now defines the visual
style of the badge (`light`, `solid`, `outline` or `dot`) and the new `color`
prop defines its color. Passing a color in `variant` still works, but it's
deprecated:

```twig
{# before #}
<twig:ea:Badge variant="success">Paid</twig:ea:Badge>

{# after #}
<twig:ea:Badge color="success">Paid</twig:ea:Badge>
```

The `variant="light"` and `variant="outline"` values now mean the `light` and
`outline` styles. Use `color="light"` for the old light gray color. The color
cases of the `BadgeVariant` enum are deprecated too; use the `BadgeColor` enum.

The CSS of badges changed too: each color now defines `--badge-{color}-tone`,
`--badge-{color}-color` and `--badge-{color}-solid-color` variables, and the
`--badge-border`, `--badge-box-shadow`, `--badge-{color}-box-shadow`,
`--badge-boolean-*`, `--badge-outline-box-shadow`, `--badge-outline-color` and
`--field-language-badge-border-color` variables were removed.

## EasyAdmin 5.7.0

### The `ea` Global Twig Variable Is Removed

The 5.0 upgrade notes already said that the global `ea` Twig variable was removed,
but it was still registered by mistake (and it holds the `AdminContextProvider` service
instead of the actual admin context). It's now removed for real. Use the `ea()` Twig
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

In EasyAdmin 5.7.1 the return type changed again to `?object` (see above).

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
