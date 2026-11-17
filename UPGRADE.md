Upgrade between EasyAdmin 4.x versions
======================================

EasyAdmin 4.29.17
-----------------

### `allowedDashboards` and `deniedDashboards` Are Now Applied to CRUD Routes

The `allowedDashboards` and `deniedDashboards` options of `#[AdminRoute]` were ignored in CRUD
controllers and their actions, so their routes were generated in every dashboard. Now they're
applied: the options of the class attribute apply to all the routes of the CRUD controller
(including the built-in actions) and the options of an action attribute override them.

When using pretty URLs, the legacy URLs that select the CRUD controller with the
`crudControllerFqcn` and `crudAction` query parameters now return a 404 error when the dashboard
excludes that controller or action (with these options or with the `allowedControllers` and
`deniedControllers` options of `#[AdminDashboard]`).

If your CRUD controllers or their actions use these options, check that they list the right
dashboards.

### Dashboard Route Options Are Now Applied to All the Dashboard Routes

The `requirements`, `host`, `schemes` and `condition` defined in the `routeOptions` argument of
`#[AdminDashboard]` were only applied to the route of the dashboard `index()`. Now they're also
applied to all the other routes of the dashboard (the routes of its CRUD controllers and of the
`#[AdminRoute]` attributes). The requirements, host and schemes of an `#[AdminRoute]` attribute
win over the ones of the dashboard, and its condition is combined with the dashboard condition
using `and`.

If your dashboards use these options, check that all their routes must have the same
restrictions, because the requests that don't match them now return a 404 error. For example, a
dashboard `condition` that only allows GET requests now also blocks the forms of the backend.

### Custom Actions Check Their Permissions When They Are Run

Custom actions (`linkToCrudAction()`, custom batch actions and `linkToRoute()` with a route of the
same CRUD controller) now check `setPermission()` and `disable()` when their URL is requested,
not only when rendering their buttons. Users without the permission see a 403 error. If several
actions run the same controller method, users can run it when they're allowed to run at least
one of those actions.

If some users must run a custom action without seeing its button, give them the permission of
that action.

The batch delete action now also checks the permission of the `batchDelete` action and the
entity permission of each selected entity. If any of them is denied, nothing is deleted.

### `setPermissions()` No Longer Removes the Permissions of Other Actions

The `setPermissions()` method of `Actions` replaced all the action permissions defined before
calling it. For example, a CRUD controller that called
`setPermissions([Action::NEW => 'ROLE_ADMIN'])` removed the permission that the dashboard defined
for `Action::DELETE`.

Now `setPermissions()` works like calling `setPermission()` for each action: it only adds or
changes the permissions of the given actions. Calling it with an empty array changes nothing.

If you used `setPermissions()` to remove the permissions defined before (in the dashboard, in a
parent CRUD controller or in an actions extension), set the permission of those actions to an
empty string:

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

The CRUD forms nested in other forms (`AssociationField::renderAsEmbeddedForm()` and
`CollectionField::useEntryCrudForm()`) now apply the `setEntityPermission()` option of their CRUD
controller, as the pages of that controller do:

* if the user can't access the associated entity, or any of the entries of the collection, the
  field is removed from the form and its property doesn't change when submitting the form;
* if the entity permission denies new entities (it's checked with a `null` subject, as in the
  `new` page), the collection doesn't allow adding entries and the embedded form of an empty
  association is removed.

If the entity permission is checked by a voter that only supports entity instances, it now
denies new entries to all users. If users must create those entities in the nested form, make
the voter also vote when the subject is `null`:

```php
protected function supports(string $attribute, mixed $subject): bool
{
    return self::EDIT === $attribute && (null === $subject || $subject instanceof Address);
}
```

### The Default Field Templates Escape the Contents of Fields

The `crud/field/text` template is the default template of all fields, and it printed their
contents without escaping them. Only `TextField` and `TextareaField` escaped their contents
before passing them to the template. The contents of the other fields that use this template
(fields defined as a string or with `Field::new()` for unmapped or nested properties like
`'fullName'` or `'author.name'`, `SlugField` and custom fields) were rendered as HTML.

Now the `crud/field/text`, `crud/field/textarea` and `crud/field/choice` templates escape the
contents, unless the field configurator marks them as safe with the
`FieldDto::OPTION_FORMATTED_VALUE_IS_SAFE_HTML` custom option. `TextField`, `TextareaField`,
`ChoiceField` and the strings returned by `formatValue()` are rendered as before.

If one of those fields must render HTML contents, use a `TextField` with the `renderAsHtml()`
option or return the HTML contents in `formatValue()`:

```php
// Before
yield Field::new('statusBadge');

// After
yield TextField::new('statusBadge')->renderAsHtml();
```

If your application overrides those templates and prints `field.formattedValue|raw`, apply the
`raw` filter only when `field.customOptions.get('formattedValueIsSafeHtml')` is `true`. If a
custom field configurator sets HTML contents as the formatted value of a field rendered with
those templates, set that custom option to `true` too.

### The Values of the Entity Placeholders Are Escaped

Page titles, entity labels and action labels are rendered as HTML. Now EasyAdmin escapes the
values of the `%entity_as_string%`, `%entity_id%` and `%entity_short_id%` placeholders used in
them, because they come from the entity data. Also, `%entity_id%` and `%entity_short_id%` are
now the ID of the loaded entity instead of the ID typed in the URL.

If the `__toString()` method of an entity returns HTML on purpose and you show it with
`%entity_as_string%`, it's now displayed as text. Use a closure instead and return the HTML
from it:

```php
// Before
$crud->setPageTitle(Crud::PAGE_DETAIL, '%entity_as_string%');

// After
$crud->setPageTitle(Crud::PAGE_DETAIL, static fn (Product $product): string => (string) $product);
```

The `translationParameters` of `ea.i18n` contain the escaped values. If a custom template
translates a message with these parameters and doesn't print it with the `raw` filter, the
values are escaped twice (`O'Neil` is shown as `O&#039;Neil`). Remove these placeholders from
that message and pass the value as your own parameter:

```twig
{# Before #}
{{ 'Delete %entity_as_string%?'|trans(ea.i18n.translationParameters) }}

{# After #}
{{ 'Delete %name%?'|trans({'%name%': entity.instance.name}) }}
```

### Entity Labels of Expanded Choice Fields Are Now Escaped

When an `AssociationField` (or any field that uses Symfony's `EntityType`) sets the `expanded`
form option, the labels of its checkboxes and radio buttons (the result of `__toString()` or of
the `choice_label` option of the related entities) were rendered as HTML. Now they are escaped,
like in the dropdown widget. The label of the field itself can still contain HTML.

If those labels must render HTML that you trust, call `renderAsHtml()` on the
`AssociationField`:

```php
yield AssociationField::new('categories')
    ->setFormTypeOption('expanded', true)
    ->renderAsHtml();
```

EasyAdmin 4.29.5
----------------

When using pretty URLs, it's deprecated to define custom CRUD actions without applying the
`#[AdminRoute]` attribute to them. In EasyAdmin 5.x, custom actions without this attribute
will be ignored and code like `->linkToCrudAction('foo')` will no longer work:

```php
// Before

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

// After
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

EasyAdmin 4.29.0
----------------

The `linkToCrud()` method used to link to CRUD controllers from the main menu of the
dashboard is deprecated in favor of the new `linkTo()` method:

```php
// Before
yield MenuItem::linkToCrud('Categories', 'fa fa-tags', Category::class);
yield MenuItem::linkToCrud('Blog Posts', 'fa fa-file-text', BlogPost::class);
yield MenuItem::linkToCrud(null, null, Comment::class);

// After
yield MenuItem::linkTo(CategoryCrudController::class, 'Categories', 'fa fa-tags');
yield MenuItem::linkTo(BlogPostCrudController::class, 'Blog Posts', 'fa fa-file-text');
yield MenuItem::linkTo(CommentCrudController::class);
```

EasyAdmin 4.26.0
----------------

Some methods related to actions have been deprecated in favor of equivalent
methods with better names:

```php
// Before
$action->displayAsLink()->...
$action->displayAsButton()->...
$action->displayAsForm()->...

// After
$action->renderAsLink()->...
$action->renderAsButton()->...
$action->renderAsForm()->...
```

EasyAdmin 4.25.0
----------------

The global `ea` variable injected in all templates is deprecated.
Use the equivalent `ea()` Twig function, which returns the current context
of the EasyAdmin application.

```twig
// Before
{{ ea.i18n.translationDomain }}

// After
{{ ea().i18n.translationDomain }}
```

EasyAdmin 4.24.8
----------------

Starting with this version, PHPStan will report an error if a class extends
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

EasyAdmin 4.22.0
----------------

The `referrerUrl` property and the `getReferrerUrl()` method of `BatchActionDto`
are deprecated. This is similar to the rest of deprecations of features related
to the "referrer URL".

The referrer URL is now handled automatically inside EasyAdmin. In your own
batch actions, you can redirect to a specific URL (built with the `AdminUrlGenerator`)
or get the referrer URL from the HTTP headers provided by browsers:

```php
// Before
return $this->redirect($batchActionDto->getReferrer());

// After
return $this->redirect($adminContext->getRequest()->headers->get('referer'));
```

EasyAdmin 4.20.0
----------------

### Country Flags now Use a Flag Twig Component

Instead of rendering country flags (in `CountryField`) using an `<img>` tag,
they are now rendered as plain `<svg>` files using a Twig component. This change
removes hundreds of lines in our `manifest.json` file and also removes a JavaScript
dependency. Flags still look and work the same as before.

If you used the included country flags in your own templates (which is rare and
not documented) you need to do the following change:

```twig
{# Before #}
<img class="country-flag" height="17" alt="{{ country_name }}" title="{{ country_name }}" src="{{ asset('images/flags/' ~ flag_code ~ '.svg', ea.assets.defaultAssetPackageName) }}">

{# After #}
<twig:ea:Flag countryCode="{{ flag_code }}" height="17" />
```

EasyAdmin 4.18.0
----------------

### Reverted FontAwesome Icon Changes

In EasyAdmin 4.16.0, we introduced a feature allowing the use of custom icon
sets in addition to the default FontAwesome icons. As part of this update,
FontAwesome icons were changed to be rendered as inline SVGs instead of being
applied via CSS classes. **This change has been reverted in this version**,
restoring the previous behavior so you can continue using FontAwesome icons as
before in EasyAdmin.

EasyAdmin 4.17.0
----------------

### Pretty URLs Changed Their Url Patterns

This is a small BC break. When using pretty URLs, the generated URLs used
underscores and they now use dashes and snake case. For example, before an URL
could be `/admin/blog_post/batchDelete` and now it's `/admin/blog-post/batch-delete`

The route names remain the same (e.g. `admin_blog_post_batch_delete`) and you
probably always generate URLs using the route name, so this BC break won't impact you.

EasyAdmin 4.16.0
----------------

### FontAwesome Icons Are Now Inlined as SVGs

**REVERTED** This change was reverted in EasyAdmin 4.18.0. If you use FontAwesome
icons, you don't have to do any change.

EasyAdmin 4.14.0
----------------

### Added Pretty URLs Support

Starting from 4.14.0 version, EasyAdmin includes a custom route loader that
can generate pretty URLs in your backend. Enable this feature by creating the
following routing file in your application:

```yaml
# config/routes/easyadmin.yaml
easyadmin:
    resource: .
    type: easyadmin.routes
```

EasyAdmin 4.11.0
----------------

### Updated the `MenuItemMatcherInterface`

The `MenuItemMatcherInterface` has changed as follows:

  * The `isSelected(MenuItemDto $menuItemDto)` method has been removed
  * The `isExpanded(MenuItemDto $menuItemDto)` method has been removed
  * A new `markSelectedMenuItem(array<MenuItemDto> $menuItems)` method has been added

Read the comments in the code of the `MenuItemMatcher` class to learn about the
new menu item matching logic.

EasyAdmin 4.10.0
----------------

### Updated the Default Title of Detail Page

The default title of the `detail` page in previous versions was `%entity_as_string%`
which is a placeholder that refers to the value returned by the `__toString()`
method of the entity.

This can potentially result in a XSS vulnerability because page titles and other
elements are rendered with the `raw` Twig filter (to allow you to customize the
contents with HTML tags).

Starting from EasyAdmin 4.10.0, the default page title is `%entity_label_singular% <small>(#%entity_short_id%)</small>`,
which only contains safe items that will never result in a XSS issue. If you
want to keep the previous page title (because you don't include user-generated
contents in `__toString()` or because you sanitize all user-submitted data) you
can add the following to your dashboard and all your CRUD controllers will use
that page title:

    class DashboardController extends AbstractDashboardController
    {
        // ...

        public function configureCrud(Crud $crud): Crud
        {
            return $crud
                // ...
                ->setPageTitle('detail', '%entity_as_string%')
            ;
        }
    }

EasyAdmin 4.8.11
----------------

EasyAdmin URLs no longer include the `referrer` query parameter, and the
`AdminContext:getReferrer()` method is deprecated.

This change is part of the long-term project to simplify URLs, with the goal of
using pretty URLs in the future. If you still need to access the referrer, you
can retrieve it from the HTTP headers provided by browsers:

```php
// Before
return $this->redirect($context->getReferrer());

// After
return $this->redirect($context->getRequest()->headers->get('referer'));
```

EasyAdmin 4.8.0
---------------

### Form Panels are now called Form Fieldsets

You can still use `FormField::addPanel()` but it's deprecated and it will be
removed in EasyAdmin 5.0.0. To fix the deprecation, "Find & Replace" in your IDE:

    // Before
    yield FormField::addPanel('...');

    // After
    yield FormField::addFieldset('...');

If your application uses custom advanced features, you might need to change some
other occurrences of "panel" such as CSS styles (`.form-panel` -> `.form-fieldset`)
and form attributes in `CrudFormType` (`$formFieldOptions['ea_form_panel']` ->
`$formFieldOptions['ea_form_fieldset'] = $currentFormFieldset`)

EasyAdmin 4.6.0
---------------

### New formatted value for Country field

This is a backward compatibility break that only affects you if you customize
the default `crud/field/country.html.twig` template or if you use a custom
template fo render `Country` fields.

Starting from this EasyAdmin version, `Country` fields allow to select more
than one value. That's why the type of the formatted value has changed from
`?string` to `?array`. E.g. if the value of your entity property is `ES`;
before, `field.formattedValue` stored the string `'Spain'` and now it stores
the array `['ES' => 'Spain']`.

The country code (used to display the country flag) is now the key of the new
array. Before, you had to use an internal propery called `flagCode` which has
been removed.

EasyAdmin 4.4.0
---------------

### Multilingual dashboards

EasyAdmin now supports multilingual dashboards. First, add the `_locale` parameter
in the URL of your dashboard (e.g. `/admin/{_locale}`) to enable the default
Symfony locale listener that handles the locale switching. If you can't do this,
you'll need to implement your own logic to handle the request locale in a way
compatible with Symfony.

After that, call the `setLocales()` method in the dashboard configuration class,
passing an array of locales that should be exposed in the interface.

EasyAdmin 4.2.0
---------------

### Signature changes

We've changed how translations are managed internally in EasyAdmin. Before we
passed translated contents to templates. Now we pass Symfony's "translatable"
objects to templates.

This means that many classes have been changed to allow using `TranslatableMessage` objects
in places where previously only `string`, `false` or `null` were allowed.
Return types were also loosened to allow returning `TranslatableMessage` where applicable.

In practice this should not affect to most applications because `TranslatableMessage`
objects gracefully transform to strings when needed. However, you might need to
update some checks where you only expected string scalar values and now you might
also get `TranslatableMessage` objects.

Full list of changes in final classes:

    Config\Action (new, setLabel); only docblocks and deprecation logic
    Config\Menu*MenuItem (constructors)
    Config\MenuItem (linkTo*, section, subMenu)
    Dto\ActionDto (getLabel, setLabel and private field)
    Dto\CrudDto (getEntityLabelInSingular, setEntityLabelInSingular,getEntityLabelInPlural, setEntityLabelInPlural, setCustomPageTitle, getHelpMessage, setHelpMessage)
    Dto\FieldDto (getLabel, setLabel, getHelp, setHelp)
    Dto\FilterDto (getLabel, setLabel); only docblocks
    Dto\MenuItemDto (getLabel, setLabel)
    Field*Field (new); only docblocks
    Field\FormField (addPanel, addTab)

List of signature changes in non-final classes and traits:

    Config\Crud (setHelp)
    Field\FieldTrait (setLabel, setHelp); setLabel only in docblock

### New setTranslatableChoices() method in `ChoiceField`

Use this method when defining choice labels with translatable objects. For example:

    yield ChoiceField::new('...')->setTranslatableChoices([
        'paid' => t('Paid Invoice'),
        'pending' => t('Invoice Sent but Unpaid'),
        'refunded' => 'Refunded Invoice', // You can mix strings with TranslatableMessage objects
    ]);

Upgrade between EasyAdmin 4.x versions
======================================

EasyAdmin 4.1.0
---------------

### Updated Country Field Flags

Flags that are optionally displayed in `CountryField` have been redesigned and
updated their format from `.png` to `.svg`. This doesn't require any change in
your application, but if you are using the flag images in your own custom designs,
update the path of the images:

```
# Before
<img alt="Flag of Panama" src="/bundles/easyadmin/images/flags/PA.png">

# After
<img alt="Flag of Panama" src="/bundles/easyadmin/images/flags/PA.svg">
```

### Removed URL signatures

Backend URLs no longer include signatures, because they don't provide any
additional security. The following classes and methods are deprecated:

  * `AdminUrlGenerator::addSignature()` method
  * `AdminUrlGenerator::getSignature()` method
  * `UrlSigner` class and service
  * `Dashboard::disableUrlSignatures()` method

The validity of URL signatures is no longer checked either. If you add signatures
manually, you'll need to check them too.
