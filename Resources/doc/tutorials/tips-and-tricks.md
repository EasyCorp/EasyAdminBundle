Tips and Tricks
===============

Better Organizing Backend Configuration
---------------------------------------

The recommended way to start configuring your backend is to use the
`app/config/config.yml` file and put your configuration under the `easy_admin`
key. However, for large backends this configuration can be very long.

In those cases, it's better to create a new `app/config/admin.yml` file to
define all the configuration related to the backend and then, import that
file from the general `config.yml` file:

```yaml
# app/config/config.yml
imports:
    - { resource: parameters.yml }
    - { resource: security.yml }
    - { resource: services.yml }
    - { resource: admin.yml }  # <-- add this line

# app/config/admin.yml      # <-- create this file
easy_admin:
    # ...
    # copy all the configuration originally defined in config.yml
    # ...
```

Improving Backend Performance
-----------------------------

EasyAdmin does an intense use of Doctrine metadata introspection to generate
the backend on the fly without generating any file or resource. For complex
backends, this process can add a noticeable performance overhead.

Fortunately, Doctrine provides a simple caching mechanism for entity metadata.
If your server has APC installed, enable this cache just by adding the
following configuration:

```yaml
# app/config/config_prod.yml
doctrine:
    orm:
        metadata_cache_driver: apc
```

In addition to `apc`, Doctrine metadata cache supports `memcache`, `memcached`,
`xcache` and `service` (for using a custom cache service). Read the
documentation about [Doctrine caching drivers](http://symfony.com/doc/current/reference/configuration/doctrine.html#caching-drivers).

Note that the previous example configures metadata caching in `config_prod.yml`
file, which is the configuration used for the production environment. It's not
recommended to enable this cache in the development environment to avoid having
to clear APC cache or restart the web server whenever you make any change to
your Doctrine entities.

This simple metadata cache configuration can improve your backend performance
between 20% and 30% depending on the complexity and number of your entities.

Use a Custom Dashboard as the Index Page of the Backend
-------------------------------------------------------

By default, the index page of the backend is the `list` view of the first
configured entity. If you want to display instead a custom page or dashboard,
[override the default AdminController] [override-admin-controller] and use
the following code:

```php
// src/AppBundle/Controller/AdminController.php
namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use JavierEguiluz\Bundle\EasyAdminBundle\Controller\AdminController as BaseAdminController;

class AdminController extends BaseAdminController
{
    /**
     * Don't forget to add this route annotation!
     *
     * @Route("/admin/", name="admin")
     */
    public function indexAction(Request $request)
    {
        // if the URL doesn't include the entity name, this is the index page
        if (null === $request->query->get('entity')) {
            // define this route in any of your own controllers
            return $this->redirectToRoute('admin_dashboard');
        }

        // don't forget to add this line to serve the regular backend pages
        return parent::indexAction($request);
    }

    // ...
}
```

Beware that the `index()` method of the default `AdminController` defines the
`admin` route, which is used to generate every backend URL. This means that
when overriding the `index()` method in your own controller, you must also
redefine the `@Route()` annotation. Otherwise, the backend will stop working.

Create a Read-Only Backend
--------------------------

Disable the `delete`, `edit` and `new` actions for all views and the users
won't be able to add, modify or remove any information:

```yaml
easy_admin:
    list:
        actions: ['-edit', '-new']
    show:
        actions: ['-delete', '-edit']
```

Unloading the Default JavaScript and Stylesheets
------------------------------------------------

EasyAdmin uses Bootstrap CSS and jQuery frameworks to build the interface.
In case you want to unload these files in addition to loading your own assets,
override the default `layout.html.twig` template and empty the
`head_stylesheets` and `body_javascripts` Twig blocks.

Read the [Advanced Design Customization] [advanced-design-customization]
tutorial to learn how to override default templates.

[override-admin-controller]: ./customizing-admin-controller.md
[advanced-design-customization]: ./advanced-design-customization.md
