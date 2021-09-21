Upgrade between EasyAdmin 3.x versions
======================================

EasyAdmin 3.5.0
---------------

### Redesigned Interface

EasyAdmin interface has been completely redesigned.
Read [this blog post](https://easycorp.github.io/blog/posts/redesigning-easyadmin)
for more details.

If you have integrated Symfony actions into your backend, you probably defined
some custom styles for them to match the rest of the backend design. In those
cases, you'll need to update your custom styles to match the new design.

### Updated some Page Titles

The default titles of the `detail` and `edit` pages have changed:

    // Before
    'detail' => '%entity_label_singular% <small>(#%entity_short_id%)</small>',
    'edit' => 'Edit %entity_label_singular% <small>(#%entity_short_id%)</small>',

    // After
    'detail' => '%entity_as_string%',
    'edit' => 'Edit %entity_label_singular%',

For example, a blog post with id `123` and `Lorem Ipsum Dolor Sit Amet` as its
string representation, would see these changes:

    // Before
    detail = Blog Post <small>(#123)</small>
    edit = Edit Blog Post <small>(#123)</small>

    // After
    detail = Lorem Ipsum Dolor Sit Amet
    edit = Edit Blog Post

If you want to revert those changes or use different page titles, read the docs
about [EasyAdmin page title options](https://symfony.com/doc/current/bundles/EasyAdminBundle/crud.html#title-and-help-options).
If you want to change the titles of all CRUD controllers, it's better to
[configure CRUD options in the Dashboard class](https://symfony.com/doc/current/bundles/EasyAdminBundle/crud.html#same-configuration-in-different-crud-controllers).

EasyAdmin 3.4.0
---------------

### Migrated to Bootstrap 5

This version of EasyAdmin upgrades Bootstrap from version 4 to version 5.
This only affects you if you have developed custom templates or changed the
default templates with your own HTML/CSS/JavaScript code.

Read the [Migrating to Bootstrap v5 guide](https://getbootstrap.com/docs/5.0/migration/)
to learn about the main changes needed to upgrade to this version.

### Removed jQuery

**jQuery library is no longer used or included in EasyAdmin**. We did this
because Bootstrap 5 moved to native JavaScript widgets, so jQuery usage is no
longer mandatory when using Bootstrap.

This only affects you if your backend has custom JavaScript code that uses
jQuery and you don't include jQuery yourself (your code relies on the jQuery
version included by EasyAdmin).

The solution depends on how you manage your custom backend assets:

* If you use Webpack Encore, add jQuery to your dependencies (`yarn add jquery --dev`)
  and follow the [jQuery integration in Webpack Encore guide](https://symfony.com/doc/current/frontend/encore/legacy-applications.html).
* If you don't use any JavaScript asset builder, download jQuery as a JavaScript
  file and store it somewhere in your application (e.g. `<your project>/public/js/jquery.min.js`)
  and then add that file in your backend with the [addJs() method](https://symfony.com/doc/current/bundles/EasyAdminBundle/design.html#adding-custom-web-assets).

### Text Elements with HTML Contents

Text fields and Textarea fields no longer strip tags in INDEX page.
Use the new `stripTags()` method to keep the previous behavior:

```php
// before
yield TextField::new('someField');

// after
yield TextField::new('someField')->stripTags();
```

### Autocomplete Fields

The `Select2` JavaScript library, which is based on jQuery, has been
replaced bt `TomSelect`, a pure-JavaScript library. This change is
transparent when using EasyAdmin features, but if you create custom
form types and want to display autocomplete fields for your `<select>`
lists, you must change the following:

```
// Before
<select data-widget="select2">
    <!-- ... -->
</select>

// After
<select data-ea-widget="ea-autocomplete">
    <!-- ... -->
</select>
```

These are the configurable options of the new autocomplete
fields and their previous equivalent options:

```
// Before
<select
    data-widget="select2"
    data-ea-escape-markup="false"
    data-select2-tags="true"
>
    <!-- ... -->
</select>

// After
<select
    data-ea-widget="ea-autocomplete"
    data-ea-autocomplete-render-items-as-html="true"
    data-ea-autocomplete-allow-item-create="true"
>
    <!-- ... -->
</select>
```

EasyAdmin 3.3.2
---------------

### CSS, JavaScript and Webpack Entries are passed as assets

This is an internal change that only affects you if your application has
customized the way EasyAdmin loads CSS/JS/Webpack entries in the templates.

In previous EasyAdmin versions, assets were passed to templates as simple
strings (e.g. `'/build/admin.css'` for a CSS asset). They were included as follows:

    {% for js_asset in js_assets %}
        <script src="{{ asset(js_asset) }}"></script>
    {% endfor %}

Starting from EasyAdmin 3.4.0, assets are passed as instances of
`EasyCorp\Bundle\EasyAdminBundle\Dto\AssetDto`, which allows to configure all
kinds of attributes and features for those assets. This is the same example as
before using the new asset objects:

    {% for js_asset in js_assets %}
        {% if js_asset.preload %}
            <link rel="preload" href="{{ ea_call_function_if_exists('preload', js_asset.value, { as: 'script', nopush: js_asset.nopush }) }}"
            {% for attr, value in js_asset.htmlAttributes %}{{ attr }}="{{ value|e('html_attr') }}" {% endfor %}>
        {% else %}
            <script src="{{ asset(js_asset.value) }}" {{ js_asset.async ? 'async' }} {{ js_asset.defer ? 'defer' }}
            {% for attr, value in js_asset.htmlAttributes %}{{ attr }}="{{ value|e('html_attr') }}" {% endfor %}></script>
        {% endif %}
    {% endfor %}

EasyAdmin 3.3.0
---------------

### JavaScript files are included in the `<head>`

JavaScript files, added via `addJsFile()` and/or `addWebpackEncoreEntry()`
in CRUD's `configureAssets()` method, are now included in the HTML
`<head>` element instead of at the bottom of the `<body>` element.

You might need to change your JavaScript code a bit to wrap it inside the following:

```js
document.addEventListener('DOMContentLoaded', () => {

    // put your JavaScript code here

});
```

This ensures that your code is run once the page has been loaded. For Webpack
Encore entries you can also set the `webpack_encore.script_attributes.defer`
option to `true` to run those scripts after the entire page is loaded.

EasyAdmin 3.2.0
---------------

This version introduced many changes related to routing and admin URLs generation.
If you don't define custom actions, you don't have to make any changes in your
application. If you define custom actions, EasyAdmin will make the needed changes
transparently in most of the cases, but in some advanced use cases, you'll need
to make some changes in your application.

### Deprecated `crudId` query parameter

**Summary**: you don't have to make any changes related to this, but you'll see
some deprecation messages if you don't update your application code.

The `crudId` query parameter has been deprecated. This parameter is a random
looking alphanumeric code calculated based on the CRUD controller FQCN and the
application `kernel.secret` parameter.

Originally it was created to hide the CRUD controller FQCN in the admin URLs,
but the inconvenience of having to retrieve the CRUD ID for a given CRUD FQCN
complicates things too much.

Starting from EasyAdmin 3.2.0, admin URLs no longer include the `crudId`
parameter. This needed changes are done transparently for you, but if you
want to fix deprecation messages, do the following changes.

**In templates**:

```twig
{# BEFORE #}
<a href="{{ ea_url().setCrudId('...') }}"> ... </a>

{# AFTER #}
<a href="{{ ea_url().setController('App\\Controller\\Admin\\SomeCrudController') }}"> ... </a>
```

**In services and controllers**:

```php
namespace App\Controller;

use App\Controller\Admin\UserCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class SomeController extends AbstractController
{
    private $adminUrlGenerator;

    public function __construct(AdminUrlGenerator $adminUrlGenerator)
    {
        $this->adminUrlGenerator = $adminUrlGenerator;
    }

    public function someAction()
    {
        // BEFORE
        $crudId = $this->crudControllerRegistry->findCrudIdByCrudFqcn(UserCrudController::class);
        $url = $this->adminUrlGenerator
            ->setCrudId($crudId)
            ->setAction('edit')
            ->setEntityId($this->getUser()->getId())
            ->generateUrl();

        // AFTER
        $url = $this->adminUrlGenerator
            ->setController(UserCrudController::class)
            ->setAction('edit')
            ->setEntityId($this->getUser()->getId())
            ->generateUrl();
    }
}
```

### Deprecated `eaContext` query parameter

The admin URL generation has been updated. The old way of generating URLs still
works, but it's deprecated. This only affects you if:

  * You generate URLs to EasyAdmin pages from outside EasyAdmin (e.g. in a normal
    Symfony controller, generate a link to "show the backend of Product entity = 3")
  * Your backend integrates normal Symfony actions (e.g. to embed some Symfony
    controller inside an EasyAdmin backend);

#### Generating links to EasyAdmin pages

If you generate URLs in Twig templates using the ``ea_url()`` function, you
don't have to make any changes. However, if you generate URLs in services or
controllers, you need to update your code.

**BEFORE** you used the ``CrudUrlGenerator`` service and called the ``build()``
method to start building the URL:

```php
namespace App\Controller;

use App\Controller\Admin\UserCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Router\CrudUrlGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class SomeController extends AbstractController
{
    private $crudUrlGenerator;

    public function __construct(CrudUrlGenerator $crudUrlGenerator)
    {
        $this->crudUrlGenerator = $crudUrlGenerator;
    }

    public function someAction()
    {
        $url = $this->crudUrlGenerator
            ->build()
            ->setController(UserCrudController::class)
            ->setAction('edit')
            ->setEntityId($this->getUser()->getId())
            ->generateUrl();
    }
}
```

**AFTER** you must use the ``AdminUrlGenerator`` service and the ``build()``
method no longer exists:

```php
namespace App\Controller;

use App\Controller\Admin\UserCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class SomeController extends AbstractController
{
    private $adminUrlGenerator;

    public function __construct(AdminUrlGenerator $adminUrlGenerator)
    {
        $this->adminUrlGenerator = $adminUrlGenerator;
    }

    public function someAction()
    {
        $url = $this->adminUrlGenerator
            ->setController(UserCrudController::class)
            ->setAction('edit')
            ->setEntityId($this->getUser()->getId())
            ->generateUrl();
    }
}
```

#### Integrating Symfony routes/controllers in EasyAdmin backends

EasyAdmin allows you to integrate normal Symfony controllers/actions in your
backends. This allows to add a menu item pointing to a Symfony route and when
clicking on it, you see the result of the controller/action inside the backend
(with the same menu and layout as the other EasyAdmin requests).

Before EasyAdmin 3.2.0, these links to Symfony routes added a query parameter
called ``eaContext`` with the ID of the Dashboard to use when serving the request.
This ``eaContext`` was needed to identify the Dashboard to use when rendering the
Symfony controller response.

Although the ``eaContext`` was added automatically by EasyAdmin to the links of
the menu items, you had to be careful and keep that query parameter in all the
URLs generated by yourself. Otherwise, you saw an exception message saying
"Variable "ea" does not exist." (because ``eaContext`` was lost and EasyAdmin
no longer can associate your request to a backend).

Starting from EasyAdmin 3.2.0, you don't have to deal with this ``eaContext``
query parameter because it no longer exists. However, that requires you to change
how you generate the links to Symfony routes.

For example, in a template:

```twig
{# BEFORE #}
{# you use the normal path() Twig function, but you have to add the eaContext
   query param that is passed to the template from the controller #}
<a href="{{ path('my_symfony_route', { id: item.id, eaContext: ea_context }) }}"> ... </a>

{# another common solution was to just merge all query params, which included eaConext #}
<a href="{{ path('my_symfony_route', app.request.query.all|merge({ id: item.id })) }}"> ... </a>

{# AFTER #}
{# you no longer need to care about eaContext, but you can't generate URLs with
   Twig's path() function. Instead, you must use EasyAdmin ea_url() function #}
<a href="{{ ea_url().setRoute('my_symfony_route', { id: item.id }) }}"> ... </a>
```

In controllers, you must do the same change: remove the ``eaContext`` parameter
and generate routes using EasyAdmin's URL generator instead of Symfony's URL
generator.

**BEFORE**

```php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class SomeController extends AbstractController
{
    public function someAction(Request $request)
    {
        // ...

        return $this->redirectToRoute('my_symfony_route', [
            'id' => $this->getUser()->getId(),
            // you had to keep this parameter in all your URLs
            'eaContext' => $request->query->get('eaContext'),
        ]);

        $this->render('some_template.html.twig', [
            '...' => '...',
            // you had to keep this parameter in all your templates
            'eaContext' => $request->query->get('eaContext'),
        ]);
    }
}
```

**AFTER**

```php
namespace App\Controller;

use App\Controller\Admin\UserCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class SomeController extends AbstractController
{
    private $adminUrlGenerator;

    public function __construct(AdminUrlGenerator $adminUrlGenerator)
    {
        $this->adminUrlGenerator = $adminUrlGenerator;
    }

    public function someAction(Request $request)
    {
        // ...

        return $this->redirect($this->adminUrlGenerator->setRoute('my_symfony_route', [
            'id' => $this->getUser()->getId(),
        ]->generateUrl());

        $this->render('some_template.html.twig', [
            '...' => '...',
        ]);
    }
}
```

EasyAdmin 3.1.0
---------------

* `CrudControllerInterface` added two new methods: `createEditFormBuilder()` and
  `createNewFormBuilder()` (and they were implemented in `AbstractCrudController`)
