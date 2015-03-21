Chapter 6. Customizing the New and Edit Views
=============================================

The `new` view is displayed when creating a new item of the given entity,
whereas the `edit` view is displayed when editing any entity instance. Both
views are pretty similar, so most of the times you apply them the same
customization.

Instead of duplicating the configuration for both views, you can define a new
*virtual* `form` view with the common configuration:

```yaml
easy_admin:
    entities:
        Customer:
            class: AppBundle\Entity\Customer
            form:  # <-- 'form' is applied to both 'new' and 'edit' views
                fields:
                    - 'id'
                    - { property: 'email', type: 'email', label: 'Contact' }
                    # ...
    # ...
```

Any option defined in the `form` view will be copied into the `new` and
`edit` views. However, any option defined in the `edit` and `new` view
overrides the corresponding `form` option. In other words, always use the
`form` action to define the common configuration, and then define in the `new`
and `edit` views just the specific options you want to override:

```yaml
easy_admin:
    entities:
        Customer:
            class: AppBundle\Entity\Customer
            form:
                fields: ['id', 'name', 'email']
                title:  'Add customer'
            new:
                fields: ['name', 'email']
            edit:
                title:  'Edit customer'
    # ...
```

The above configuration is equivalent to the following:

```yaml
easy_admin:
    entities:
        Customer:
            class: AppBundle\Entity\Customer
            new:
                fields: ['name', 'email']
                title:  'Add customer'
            edit:
                fields: ['id', 'name', 'email']
                title:  'Edit customer'
    # ...
```

Customize the Title of the Page
-------------------------------

By default, the title of the `edit`/`new` pages displays a very generic title.
Define the `title` option to set a custom page title:

```yaml
# app/config/config.yml
easy_admin:
    entities:
        Customer:
            class: AppBundle\Entity\Customer
            form:
                title: 'Modify customer information'
        # ...
```

The `title` option can include any of the following two variables:

  * `%entity_name%`, resolves to the class name of the current entity (e.g.
    `Customer`, `Product`, `User`, etc.)
  * `%entity_id%`, resolves to the value of the primary key of the entity being
    edited. Obviously, this variable is not available for the title of the
    `new` action. Even if the option is called `entity_id`, it also works for
    primary keys with names different from `id`.

Beware that, in Symfony applications, YAML values enclosed with `%` and `%`
have a special meaning. Use two consecutive `%` characters to avoid any issue:

```yaml
easy_admin:
    entities:
        Customer:
            class: AppBundle\Entity\Customer
            form:
                title: 'Modify customer %%entity_id%% information'
        # ...
```

Customize which Fields are Displayed
------------------------------------

By default, the forms used to create and edit entities display all their
properties. Customize any of these forms for any of your entities using the
`fields` option:

```yaml
easy_admin:
    entities:
        Customer:
            class: AppBundle\Entity\Customer
            form:
                fields: ['firstName', 'secondName', 'phone', 'email']
    # ...
```

If any of the fields is an association with another entity, the form will
display it as a `<select>` list. The values displayed in this list will be the
values returned by the magic `__toString()` PHP method. Define this method in
all your entities to avoid errors and to define the textual representation of
the entity.

Customize the Order of the Fields Displayed
-------------------------------------------

By default, forms show their fields in the same order as they were defined in
the associated entities. You could customize the fields order just by
reordering the entity properties, but it's more convenient to just define the
order using the `fields` option:

```yaml
easy_admin:
    entities:
        Customer:
            class: AppBundle\Entity\Customer
            form:
                fields: ['firstName', 'secondName', 'phone', 'email']
    # ...
```

Customize the Form Fields Appearance
------------------------------------

By default, all form fields are displayed with the same visual style, they
don't show any help message, and their label and field type are inferred from
their associated Doctrine property.

In case you want to customize any or all form fields, use the extended form
field configuration showed below:

```yaml
easy_admin:
    entities:
        Customer:
            class: AppBundle\Entity\Customer
            form:
                fields:
                    - 'id'
                    - { property: 'email', type: 'email', label: 'Contact' }
                    - { property: 'code', type: 'number', label: 'Customer Code', class: 'input-lg' }
                    - { property: 'notes', help: 'Use this field to add private notes and comments about the client' }
                    - { property: 'zone', type: 'country' }
    # ...
```

These are the options that you can define for form fields:

  * `property` (mandatory): the name of the related Doctrine entity property.
    It can be a real property or a "virtual property" based on an entity
    method. This is the only mandatory option.
  * `type` (optional): the [Symfony Form Type](http://symfony.com/doc/current/reference/forms/types.html)
    used to render the field. If you don't specify a type, EasyAdmin will
    guess the best type for it.
  * `label` (optional): the title that will be displayed for the form field.
    The default title is the "humanized" version of the property name.
  * `help` (optional): the help message that will be displayed below the
    form field.
  * `class` (optional): the CSS class that will be applied to the form field
    widget. For example, to display a big input field, use the Bootstrap 3
    class called `input-lg`.

### Translate Form Field Labels

Read *"Translate Column Labels"* section of the [chapter 4](4-customizing-list-view.md).

Use Custom Doctrine Types in Forms
----------------------------------

When your application defines custom Doctrine DBAL types, you must define a
related custom form type before using them as form fields. Imagine that your
application defines a `UTCDateTime` type to convert the timezone of datetime
values to UTC before saving them in the database.

If you add that type in a form field as follows, you'll get an error message
saying that the `utcdatetime` type couldn't be loaded:

```yaml
easy_admin:
    entities:
        Customer:
            class: AppBundle\Entity\Customer
            form:
                fields:
                    - { property: 'createdAt', type: 'utcdatetime' }
                    # ...
    # ...
```

This problem is solved defining a custom `utcdatetime` Form Type related to
this custom Doctrine DBAL type. Read the
[How to Create a Custom Form Field Type](http://symfony.com/doc/current/cookbook/form/create_custom_field_type.html)
article of the official Symfony documentation to learn how to define custom
form types.

Customize the Actions Used to Create and Edit Entities
------------------------------------------------------

By default, new and edited entities are persisted without any further
modification. In case you want to manipulate the entity before persisting it,
you can override the methods used by EasyAdmin.

Similarly to customizing templates, you need to use the Symfony bundle
[inheritance mechanism](http://symfony.com/doc/current/book/templating.html#overriding-bundle-templates)
to override the controller used to generate the backend. Among many other
methods, this controller contains two methods which are called just before the
entity is persisted:

```php
protected function prepareEditEntityForPersist($entity)
{
    return $entity;
}

protected function prepareNewEntityForPersist($entity)
{
    return $entity;
}
```

Suppose you want to automatically set the slug of some entity called `Article`
whenever the entity is persisted. First, create a new controller inside any of
your own bundles. Make this controller extend the `AdminController` provided by
EasyAdmin and include, at least, the following contents:

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

Now you can add in this new controller any of the original controller's
methods to override them. Let's add `prepareEditEntityForPersist()` and
`prepareNewEntityForPersist()` to set the `slug` of the `Article` entity:

```php
// src/AppBundle/Controller/AdminController.php
namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use JavierEguiluz\Bundle\EasyAdminBundle\Controller\AdminController as EasyAdminController;
use AppBundle\Entity\Article;

class AdminController extends EasyAdminController
{
    /**
     * @Route("/admin/", name="admin")
     */
    public function indexAction(Request $request)
    {
        return parent::indexAction($request);
    }

    protected function prepareEditEntityForPersist($entity)
    {
        if ($entity instanceof Article) {
            return $this->updateSlug($entity);
        }
    }

    protected function prepareNewEntityForPersist($entity)
    {
        if ($entity instanceof Article) {
            return $this->updateSlug($entity);
        }
    }

    private function updateSlug($entity)
    {
        $slug = $this->get('app.slugger')->slugify($entity->getTitle());
        $entity->setSlug($slug);

        return $entity;
    }
}
```

The example above is trivial, but your custom admin controller can be as
complex as needed. In fact, you can override any of the original controller's
methods to customize the backend as much as you need.

Advanced Customization of the Fields Displayed in Forms
-------------------------------------------------------

The previous sections showed how to tweak the fields displayed in the `edit`
and `new` forms using some simple options. When the field customization is
more advanced, you should override the `configureEditForm()` method in your own
admin controller.

In this example, the form of the `Event` entity is tweaked to change the
regular `city` field by a `choice` form field with custom and limited choices:

```php
// src/AppBundle/Controller/AdminController.php
namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use JavierEguiluz\Bundle\EasyAdminBundle\Controller\AdminController as EasyAdminController;
use AppBundle\Entity\Event;

class AdminController extends EasyAdminController
{
    /**
     * @Route("/admin/", name="admin")
     */
    public function indexAction(Request $request)
    {
        return parent::indexAction($request);
    }

    public function createEditForm($entity, array $entityProperties)
    {
        $editForm = parent::createEditForm($entity, $entityProperties);

        if ($entity instanceof Event) {
            // the trick is to remove the default field and then
            // add the customized field
            $editForm->remove('city');
            $editForm->add('city', 'choice', array('choices' => array(
                'London', 'New York', 'Paris', 'Tokyo'
            )));
        }

        return $editForm;
    }
}
```
