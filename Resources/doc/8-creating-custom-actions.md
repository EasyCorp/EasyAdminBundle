Chapter 8. Creating custom actions
========================================

For now, the backoffice handles only some most used actions: `list`, `edit`,
`new`, `show`, `search` and `delete`.

If you want to create custom actions, like a dashboard for example, you have 
to extend the `AdminController` and create the actions, and also, you need to
add your actions to your configuration.
 
For this, you have 2 ways of adding custom actions in config:

```yml
# app/config/config.yml
easy_admin:
    # These actions will be the ones showed on lists for ALL entities
    list_actions: [ edit, show, customActionGlobal ]
    entities:
        Post:
            class: AppBundle\Entity\Post
            # These actions are appended to the original "list_actions" array, only for this entity
            list_actions: [ 'customActionForEntity' ] 
```

Next, you have to create your own controller extending EasyAdmin's `AdminController`,
and switch the routing to load your controller instead of EasyAdmin's one:

```yml
# app/config/routing.yml
easy_admin_bundle:
    resource: "@AppBundle/Controller/AdminController.php"
    type:     annotation
    prefix:   /admin
```

And then create your controller:

```php
// src/AppBundle/Controller/AdminController.php
namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use JavierEguiluz\Bundle\EasyAdminBundle\Controller\AdminController as EasyAdminController;

class AdminController extends EasyAdminController
{
    /**
     * @Route("/admin/", name="admin")
     */
    public function indexAction(Request $request)
    {
        return parent::indexAction($request);
    }

}
```

That's it! Now, all you need to do is create the custom actions inside your controller.

**Note:** Don't forget to append `Action` to your action's name, as usual in Symfony.

```php
// src/AppBundle/Controller/AdminController.php

    public function customActionGlobalAction() {
        // Your own logic here
    }

    public function customActionForEntityAction() {
        if ($this->entity['name'] === 'Post') {
            // Your own logic here
        }
    }

```
