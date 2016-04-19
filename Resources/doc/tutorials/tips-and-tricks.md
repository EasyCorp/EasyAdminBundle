Tips and Tricks
===============

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
     * @Route("/", name="easyadmin")
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
`easyadmin` route, which is used to generate every backend URL. This means that
when overriding the `index()` method in your own controller, you must also
add the `@Route()` annotation. Otherwise, the backend will stop working.

Create a Read-Only Backend
--------------------------

Disable the `delete`, `edit` and `new` actions for all views and the users
won't be able to add, modify or remove any information:

```yaml
easy_admin:
    disabled_actions: ['delete', 'edit', 'new']
```

Unloading the Default JavaScript and Stylesheets
------------------------------------------------

EasyAdmin uses Bootstrap CSS and jQuery frameworks to build the interface.
In case you want to unload these files in addition to loading your own assets,
override the default `layout.html.twig` template and empty the
`head_stylesheets` and `head_javascript` Twig blocks.

Read the [Advanced Design Customization][1] section to learn how to override
default templates.

Removing Action Labels and Displaying Just Icons
------------------------------------------------

By default, the actions showed in the `list` view table only display their
label (`Edit`, `Show`, etc.):

![Action Labels in Entity Listing](../images/easyadmin-listing-actions-label-only.png)

Adding an icon for each action is as easy as defining their `icon` option:

```yaml
easy_admin:
    list:
        actions:
            - { name: 'show', icon: 'search' }
            - { name: 'edit', icon: 'pencil' }
    # ...
```

This configuration makes the entity listing looks as follow:

![Action Labels and Icons in Entity Listing](../images/easyadmin-listing-actions-label-and-icon.png)

When displaying entities with lots of information, it may be useful to remove
the action label and display just their icons. To do so, define an empty string
for the `label` option or set its value to `false`:

```yaml
easy_admin:
    list:
        actions:
            - { name: 'show', icon: 'search', label: '' }
            - { name: 'edit', icon: 'pencil', label: '' }
            # if you prefer, set labels to false
            # - { name: 'show', icon: 'search', label: false }
            # - { name: 'edit', icon: 'pencil', label: false }
    # ...
```

This configuration makes the entity listing looks as follow:

![Action Icons in Entity Listing](../images/easyadmin-listing-actions-icon-only.png)

Making the Backend Use a Different Language Than the Public Website
-------------------------------------------------------------------

Imagine that the public part of your website uses French as its default locale.
EasyAdmin uses the same locale as the underlying Symfony application, so the
backend would be displayed in French too. How could you define a different
language for the backend?

You just need to get the `translator` service and execute the `setLocale()` method
befor executing the code of EasyAdmin. The easiest way to do that is to create
a [custom admin controller] [override-admin-controller] and override the
`initialize()` method as follows:

```php
// src/AppBundle/Controller/AdminController.php
namespace AppBundle\Controller;

use JavierEguiluz\Bundle\EasyAdminBundle\Controller\AdminController as BaseAdminController;

class AdminController extends BaseAdminController
{
    protected function initialize(Request $request)
    {
        $this->get('translator')->setLocale('en');
        parent::initialize($request);
    }
}
```

[override-admin-controller]: ./customizing-admin-controller.md
[1]: ../book/3-list-search-show-configuration.md#advanced-design-configuration
