Upgrade between EasyAdmin 3.x versions
======================================

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
