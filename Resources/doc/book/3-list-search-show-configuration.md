Chapter 3. List, Search and Show Views Configuration
====================================================

This chaper explains how to customize the `list`, `search` and `show` views.
You'll learn all their configuration options, how to override or tweak their
templates and how to completely override their behavior with custom controllers
and Symfony events.

List, Search and Show Views
---------------------------

The **List View** displays the list of items that match the given criteria and
provides automatic pagination and column sorting:

![List view interface](../images/easyadmin-list-view.png)

The **Search View** is used to display the results of any query performed by
the user. It reuses most of the design and features of the `list` view to
ensure a consistent user experience:

![Search view interface](../images/easyadmin-search-view.png)

The **Show View** is used when displaying the contents of any entity:

![Show view interface](../images/easyadmin-show-view.png)

General Configuration
---------------------

In order to make examples more concise, this section only shows the
configuration for the `list` view, but you can apply the same options to the
other `search` and `show` views.

### Customize the Title of the Page

Page titles display by default the name of the entity. Define the `title` option
to set a custom page title:

```yaml
# app/config/config.yml
easy_admin:
    entities:
        Customer:
            class: AppBundle\Entity\Customer
            label: 'Customers'
            list:
                title: "Most recent customers"
        # ...
```

The `title` option can include the following special variables:

  * `%entity_label%`, resolves to the value defined in the `label` option of
    the entity. If you haven't defined it, this value will be equal to the
    entity name. In the example above, this value would be `Customers`.
  * `%entity_name%`, resolves to the entity name, which is the YAML key used
    to configure the entity in the backend configuration file. In the example
    above, this value would be `Customer`.
  * `%entity_id%`, it's only available for the `show` view and it resolves to
    the value of the primary key of the entity being showed. Even if the option
    is called `entity_id`, it also works for primary keys with names different
    from `id`.

Beware that, in Symfony applications, YAML values enclosed with `%` and `%` have
a special meaning (they are considered container parameters). Escape these
values doubling the `%` characters:

```yaml
easy_admin:
    entities:
        Customer:
            class: AppBundle\Entity\Customer
            label: 'Customers'
            list:
                title: '%%entity_name%% listing'
        # ...
```

### Customize the Number of Rows Displayed

By default, listings in the `list` and `search` display a maximum of 15 rows.
Define the `max_results` option under the global `list` key to change this value:

```yaml
# app/config/config.yml
easy_admin:
    list:
        max_results: 30
    # ...
```

### Customize the Properties Displayed

By default, the `show` view displays all the entity properties. The `list` and
`search` views make some "smart guesses" to decide which columns to display to
make listings look good.

Use the `fields` option to explicitly set the properties to display:

```yaml
# app/config/config.yml
easy_admin:
    entities:
        Customer:
            class: AppBundle\Entity\Customer
            list:
                fields: ['id', 'firstName', 'lastName', 'phone', 'email']
    # ...
```

This option is useful to reorder the properties, because by default they are
displayed in the same order as defined in the related Doctrine entity.

In the case of the `search` view, this `fields` option defines the properties
included in the search query. Otherwise, the query is performed on all entity
properties except those with special data types, such as `binary`, `blob`,
`object`, etc.

Customize the Properties Appearance
-----------------------------------

When entity properties are not configured explicitly, the backend makes some
smart guesses to display them with the most appropriate appearance according to
their data types. If you prefer to control their appearance, start by using the
extended field configuration:

```yaml
# app/config/config.yml
easy_admin:
    entities:
        Customer:
            class: AppBundle\Entity\Customer
            list:
                fields: ['id', 'name', { property: 'email', label: 'Contact' }]
    # ...
```

Instead of using a string to define the name of the property (e.g. `'email'`)
you have to define a hash with the name of the property (`{ property: 'email'
}`) and the options you want to define for it (`{ ..., label: 'Contact' }`).

If your entity contains lots of properties, consider using the alternative YAML
syntax for sequences to improve the legibility of your backend configuration.
The following example is equivalent to the above example:

```yaml
# app/config/config.yml
easy_admin:
    entities:
        Customer:
            class: AppBundle\Entity\Customer
            list:
                fields:
                    - id
                    - name
                    - { property: 'email', label: 'Contact' }
    # ...
```

These are the options that you can define for each field:

  * `property` (mandatory): the name of the entity property which you want to
    display. The `property` option is the only mandatory option when using the
    extended field configuration format.
  * `label` (optional): the title displayed for the property. The default
    title is the "humanized" version of the property name (e.g. `published` is
    displayed as `Published` and `dateOfBirth` as `Date of birth`).
  * `template` (optional): the name of the custom template used to render the
    contents of the field. This option is fully explained later in this chapter.
  * `type` (optional): the type of data displayed. This allows to select the
    most appropriate template to display the contents. The allowed values are:
    * Any of the Doctrine types: `array`, `association`, `bigint`, `blob`,
      `boolean`, `date`, `datetime`, `datetimetz`, `decimal`, `float`, `guid`,
      `integer`, `json_array`, `object`, `simple_array`, `smallint`, `string`,
      `text`, `time`.
    * Any of the custom EasyAdmin types: `image`, `toggle`, `raw` (they are
      explained later in this chapter).

The fields of the `show` view define another two options:

  * `help` (optional): the help message displayed below the field contents.
  * `css_class` (optional): the CSS class applied to the field container element.

> **TIP**
>
> In addition to these options defined by EasyAdmin, you can define any custom
> option for the fields. This way you can create very powerful backend
> customizations, as explained in the
> [How to Define Custom Options for Entity Properties][1] tutorial.

Formatting Dates and Numbers
----------------------------

### Customizing Date and Time Properties

By default, these are the formats applied to date and time properties (read the
[date configuration options][3] in the PHP manual if you don't understand the
meaning of these formats):

  * `date`: `Y-m-d`
  * `time`:  `H:i:s`
  * `datetime`: `F j, Y H:i`

These default formats can be overridden in two ways: globally for all entities
and locally for each entity property. The global `formats` option sets the
formats for all entities:

```yaml
easy_admin:
    formats:
        date:     'd/m/Y'
        time:     'H:i'
        datetime: 'd/m/Y H:i:s'
    entities:
        # ...
```

The values of the `date`, `time` and `datetime` options are passed to the
`format()` method of the `DateTime` class, so you can use any of the
[date configuration options][3] defined by PHP.

Date/time formatting can also be defined in each property configuration using
the `format` option. This local option always overrides the global format:

```yaml
easy_admin:
    entities:
        Customer:
            class: AppBundle\Entity\Customer
            list:
                fields:
                    - { property: 'dateOfBirth', format: 'j/n/Y' }
                    # ...
    # ...
```

### Customizing Numeric Properties

Numeric properties (`bigint`, `integer`, `smallint`, `decimal`, `float`) are
formatted by default according to the locale of your Symfony application. This
formatting can be overridden globally for all entities or locally for each
property.

The global `formats` option applies the same formatting for all entities:

```yaml
easy_admin:
    formats:
        # ...
        number: '%.2f'
    entities:
        # ...
```

In this case, the value of the `number` option is passed to the `sprintf()`
function, so you can use any of the [PHP format specifiers][4].

Numeric properties can also define their formatting using the `format`
option. This local option always overrides the global format:

```yaml
easy_admin:
    entities:
        Product:
            class: AppBundle\Entity\Product
            list:
                fields:
                    - { property: 'serialNumber', format: '%010s' }
                    - { property: 'margin', format: '%01.2f' }
                    # ...
    # ...
```

Virtual Properties
------------------

Sometimes, it's useful to display values which are not entity properties. For
example, if your `Customer` entity defines the `firstName` and `lastName`
properties, you may want to display a column called `Name` with both values
merged. These are called *virtual properties* because they don't really exist as
Doctrine entity properties.

First add the virtual property to the entity configuration as any other property:

```yaml
easy_admin:
    entities:
        Customer:
            class: AppBundle\Entity\Customer
            list:
                # 'name' doesn't exist as a Doctrine entity property
                fields: ['id', 'name', 'phone', 'email']
    # ...
```

Now, if you reload the backend, you'll see that the virtual property only
displays `Inaccessible` as its value. The reason is that `name` does not match
any of the entity's properties. To fix this issue, add a new public method in
your entity called `getXxx()` or `xxx()`, where `xxx` is the name of the
virtual property (in this case the property is called `name`, so the method
must be called `getName()` or `name()`):

```php
use Doctrine\ORM\Mapping as ORM;

/** @ORM\Entity */
class Customer
{
    // ...

    public function getName()
    {
        return $this->getFirstName().' '.$this->getLastName();
    }
}
```

That's it. Reload your backend and now you'll see the value of this virtual
property. By default, virtual properties are displayed as text contents. If your
virtual property is a *boolean* value or a date, use the `type` option to set a
more appropriate data type:

```yaml
# in this example, the virtual properties 'is_eligible' and 'last_contact' define
# their 'type' option to avoid displaying them as regular text contents
easy_admin:
    entities:
        Customer:
            class: AppBundle\Entity\Customer
            list:
                fields:
                    - 'id'
                    - { property: 'is_eligible',  type: 'boolean' }
                    - { property: 'last_contact', type: 'datetime' }
    # ...
```

The main limitation of virtual properties is that you cannot sort listings
using these fields.

EasyAdmin Data Types
--------------------

In addition to the Doctrine data types, properties can use any of the following
data types defined by EasyAdmin.

### Email Data Type

It displays the contents of the property as a clickable `mailto:` link:

```yaml
easy_admin:
    entities:
        Product:
            class: AppBundle\Entity\User
            list:
                fields:
                    - { property: 'contact', type: 'email' }
                    # ...
    # ...
```

### URL Data Type

It displays the contents of the property as a clickable link which opens in a
new browser tab:

```yaml
easy_admin:
    entities:
        Product:
            class: AppBundle\Entity\User
            list:
                fields:
                    - { property: 'blogUrl', type: 'url' }
                    # ...
    # ...
```

### Telephone Data Type

It displays the contents of the property as a clickable telephone number. Beware
that some browsers don't support these links:

```yaml
easy_admin:
    entities:
        Product:
            class: AppBundle\Entity\User
            list:
                fields:
                    - { property: 'workPhoneNumber', type: 'tel' }
                    # ...
    # ...
```

### Toogle and Boolean Data Types

If an entity is editable, the `list` view applies the `type: 'toggle'` option to
all the boolean properties. This data type makes these properties be rendered as
"flip switches" that allow to toggle their values very easily:

![Advanced boolean fields](../images/easyadmin-boolean-field-toggle.gif)

When you change the value of any boolean property, an Ajax request is made to
actually change that value in the database. If something goes wrong, the switch
automatically returns to its original value and it disables itself until the
page is refreshed to avoid further issues:

![Boolean field behavior when an error happens](../images/easyadmin-boolean-field-toggle-error.gif)

In you prefer to disable these "toggles", define the `type` of the property
explicitly as `boolean`:

```yaml
easy_admin:
    entities:
        Product:
            class: AppBundle\Entity\Product
            list:
                fields:
                    - { property: 'hasStock', type: 'boolean' }
                    # ...
    # ...
```

Now the boolean value is rendered as a simple label and its value cannot be
modified from the `list` view:

![Boolean field displayed as a label](../images/easyadmin-boolean-field-label.png)

### Image Data Type

If any of your properties stores the URL or path of an image, this type allows
you to display the actual image instead of its path. In most cases, you just
need to set the `type` property to `image`.

In the following example, the `photo` property is displayed as a `<img>` HTML
element whose `src` attribute is the value stored in the property:

```yaml
easy_admin:
    entities:
        Product:
            class: AppBundle\Entity\Product
            list:
                fields:
                    - { property: 'photo', type: 'image' }
                    # ...
    # ...
```

If the property stores relative paths, define the `base_path` option to set the
path to be prefixed to the image path:

```yaml
easy_admin:
    entities:
        Product:
            class: AppBundle\Entity\Product
            list:
                fields:
                    - { property: 'photo', type: 'image', base_path: '/img/' }
                    # ...
    # ...
```

The value of the `base_path` can be a relative or absolute URL and even a
Symfony parameter:

```yaml
# relative path
- { property: 'photo', type: 'image', base_path: '/img/products/' }

# absolute path pointing to an external host
- { property: 'photo', type: 'image', base_path: 'http://static.acme.org/img/' }

# Symfony container parameter
- { property: 'photo', type: 'image', base_path: '%vich_uploader.mappings.product_image%' }
```

The image base path can also be set in the entity, to avoid repeating its
value for different properties or different views:

```yaml
easy_admin:
    entities:
        Product:
            class: AppBundle\Entity\Product
            image_base_path: 'http://static.acme.org/img/'
            list:
                fields:
                    - { property: 'photo', type: 'image' }
                    # ...
    # ...
```

The base paths defined for a property always have priority over the one defined
globally for the entity.

### Raw Data Type

All the string-based data types are escaped before displaying them. For that
reason, if the property stores HTML content, you'll see the HTML tags instead of
the rendered HTML content. In case you want to display the contents unescaped,
define the `type` option with a `raw` value:

```yaml
easy_admin:
    entities:
        Product:
            class: AppBundle\Entity\Product
            list:
                fields:
                    - { property: 'features', type: 'raw' }
                    # ...
    # ...
```

Advanced Design Configuration
-----------------------------

This section explains how to completely customize the design of the `list`,
`search` and `show` views overriding the default templates and fragments used to
render them.

### Default Templates

EasyAdmin defines seven Twig templates to create its interface. These are the
four templates related to `list`, `search` and `show` views:

  * `layout`, the common layout that decorates the `list`, `edit`, `new` and
    `show` templates;
  * `show`, renders the contents stored by a given entity;
  * `list`, renders the entity listings and the search results page;
  * `paginator`, renders the paginator of the `list` view.

Depending on your needs you can override these templates in different ways.

### Selecting the Template to Render

These are all the configuration options and paths checked before selecting the
template to render (the first template which exists is used):

  1. `easy_admin.entities.<EntityName>.templates.<TemplateName>` configuration
     option.
  2. `easy_admin.design.templates.<TemplateName>` configuration option.
  3. `app/Resources/views/easy_admin/<EntityName>/<TemplateName>.html.twig`
  4. `app/Resources/views/easy_admin/<TemplateName>.html.twig`
  5. `@EasyAdmin/default/<TemplateName>.html.twig`

The last one is the template path of the built-in templates and they are always
available. The following sections explain the first four ways to customize the
templates used by the backend.

#### Overriding the Default Templates By Configuration

If you prefer to define your custom templates in some specific location of the
application, it's more convenient to use the `templates` option.

For example, to override the `paginator` template just for the `Customer` entity,
create the `paginator.html.twig` template somewhere in your application and then,
configure its location with the `templates` option:

```yaml
easy_admin:
    entities:
        Customer:
            # ...
            templates:
                paginator: 'AppBundle:Default:fragments/_paginator.html.twig'
                # namespace syntax works too:
                # paginator: '@App/Default/fragments/_paginator.html.twig'
```

Similarly, to override some template for all entities, define the `templates`
option under the global `design` option:

```yaml
easy_admin:
    design:
        templates:
            paginator: 'AppBundle:Default:fragments/_paginator.html.twig'
            # namespace syntax works too:
            # paginator: '@App/Default/fragments/_paginator.html.twig'
    entities:
        # ...
```

#### Overriding the Default Templates By Convention

If you don't mind the location of your custom templates, consider creating them
in the `app/Resources/views/easy_admin/` directory. When the `templates` option
is not defined, EasyAdmin looks into this directory before falling back to the
default templates.

For example, to override the `paginator` template just for the `Customer` entity,
you only need to create this template in this exact location (there is no need
to define the `templates` configuration option):

```
your-project/
├─ app/
│  ├─ ...
│  └─ Resources/
│     └─ views/
│        └─ easy_admin/
│           └─ Customer/
│              └─ paginator.html.twig
├─ src/
├─ vendor/
└─ web/
```

In case you want to override the template for all entities, define the new
template right under the `easy_admin/` directory:

```
your-project/
├─ app/
│  ├─ ...
│  └─ Resources/
│     └─ views/
│        └─ easy_admin/
│           └─ paginator.html.twig
├─ src/
├─ vendor/
└─ web/
```

#### Tweaking the Design of the Default Templates

Most often than not, customizing the design of the backend is a matter of just
tweaking some element of the default templates. The easiest way to do that is
to create a new template that extends from the default one and override just the
specific Twig block you want to customize.

Suppose you want to change the search form of the `list` view. First, create a
new `list.html.twig` template as explained in the previous sections. Then, make
your template extend from the default `list.html.twig` template:

```twig
{% extends '@EasyAdmin/default/list.html.twig' %}

{# ... #}
```

Lastly, override the `search_action` block to just change that template fragment:

```twig
{% extends '@EasyAdmin/default/list.html.twig' %}

{% block search_action %}
    {# ... #}
{% endblock %}
```

### Customizing the Template Used to Render Each Property Type

In the `list`, `search` and `show` views, the value of each property is rendered
with a different template according to its type. For example, properties of type
`string` are rendered with the `field_string.html.twig` template.

These are all the available templates for each property type:

  * `field_array.html.twig`
  * `field_association.html.twig`, renders properties that store Doctrine
    associations.
  * `field_bigint.html.twig`
  * `field_boolean.html.twig`
  * `field_date.html.twig`
  * `field_datetime.html.twig`
  * `field_datetimetz.html.twig`
  * `field_decimal.html.twig`
  * `field_float.html.twig`
  * `field_id.html.twig`, special template to render any property called `id`.
    This avoids formatting the value of the primary key as a numeric value, with
    decimals and thousand separators.
  * `field_image.html.twig`, related to the special `image` data type defined by
    EasyAdmin.
  * `field_integer.html.twig`
  * `field_raw.html.twig`, related to the special `raw` data type defined by
    EasyAdmin.
  * `field_simple_array.html.twig`
  * `field_smallint.html.twig`
  * `field_string.html.twig`
  * `field_text.html.twig`
  * `field_time.html.twig`
  * `field_toggle.html.twig`, related to the special `toggle` data type defined
    by EasyAdmin for boolean properties.

In addition, there are other templates defined to render special labels:

  * `label_empty.html.twig`, used when the property to render is empty (it's
    used for arrays, collections, associations, images, etc.)
  * `label_inaccessible.html.twig`, used when is not possible to access the
    value of the property because there is no getter or public property.
  * `label_null.html.twig`, used when the value of the property is `null`.
  * `label_undefined.html.twig`, used when any kind of error or exception
    happens when trying to access the value of the property.

The same template overriding mechanism explained in the previous sections can be
applied to customize the template used to render each property. All the built-in
templates are defined under the `@EasyAdmin/default/...` namespace, so your own
templates can extend from them.

Suppose that in your backend you don't want to display a `NULL` text for `null`
values and prefer to display a more human friendly value, such as `Undefined`.
If you prefer convention over configuration, create this template in this exact
location:

```twig
{# app/Resources/views/easy_admin/label_null.html.twig #}
<span class="null">Undefined</span>
```

Create this other template to override the `label_null.html.twig` template just
for one specific entity called `Invoice`:

```twig
{# app/Resources/views/easy_admin/Invoice/label_null.html.twig #}
<span class="null">Unpaid</span>
```

If you prefer to store your custom templates somewhere in your application
instead of the `app/Resources/views/easy_admin/` directory, use the `templates`
option to define their location:

```yaml
easy_admin:
    design:
        templates:
            label_null: 'AppBundle:Default:labels/null.html.twig'
            # namespace syntax works too:
            # label_null: '@App/Default/labels/null.html.twig'
    # ...
    entities:
      Invoice:
        templates:
            label_null: 'AppBundle:Invoice:backend/label_null.html.twig'
            # namespace syntax works too:
            # label_null: '@App/Invoice/backend/label_null.html.twig'
```

Before customizing any of these templates, it's recommended to check out the
contents of the default `field_*.html.twig` and `label_*.html.twig` templates,
so you can learn about their features. Inside these templates you have access to
the following variables:

  * `field_options`, an array with the options configured for this field in the
    backend configuration file.
  * `item`, an object with the current entity instance.
  * `value`, the content of the property being rendered, which can be a variable
    of any type (string, numeric, boolean, array, etc.)
  * `view`, a string with the name of the view where the field is being rendered
    (`show` or `list`).

### Rendering Entity Properties with Custom Templates

The property templates explained in the previous section are applied to all the
properties of the same type (strings, dates, arrays, etc.) However, when your
backend is very complex, it may be useful to use a custom template just to
render a single property of some entity.

To do so, define the name of the custom template in the `template` option of
the property:

```yaml
easy_admin:
    # ...
    entities:
        Invoice:
            list:
                fields:
                    - { property: 'total', template: 'invoice_total.html.twig' }
```

The value of the `total` property is now rendered with `invoice_total.html.twig`
template instead of the default `field_float.html.twig` template. As usual,
EasyAdmin first looks for custom templates in the following locations (the first
existing template is used):

  1. `app/Resources/views/easy_admin/<EntityName>/<TemplateOptionValue>`
  2. `app/Resources/views/easy_admin/<TemplateOptionValue>`

If none of these templates exist, the value of the `template` option is
considered a Symfony template path, so you can use any of the valid template
syntaxes:

```yaml
easy_admin:
    # ...
    entities:
        Invoice:
            list:
                fields:
                    - { property: 'total', template: 'AppBundle:Invoice:total.html.twig' }
                    - { property: 'price', template: '@App/Invoice/unit_price.html.twig' }
```

Custom templates receive the same parameters as built-in templates
(`field_options`, `item`, `value` and `view`).

Customizing the Behavior of `list`, `search` and `show` Views
-------------------------------------------------------------

In the previous sections you've learned how to override or tweak the templates
associated with each view or property. This is the most common way to customize
backends because it's simple yet powerful. However, EasyAdmin provides a more
advanced customization mechanism based on PHP to customize the behavior of the
backend.

Depending on your needs you can choose any of these two customization options
(or combine both, if your backend is very complex):

  * Customization based on **controller methods**, which is easy to set up but
    requires you to put all the customization code in a single controller which
    extends from the default `AdminController` provided by EasyAdmin.
  * Customization based on **Symfony events**, which is hader to set up but
    allows you define the customization code anywhere in your application.

### Customization Based on Controller Methods

This technique requires you to create a new controller in your Symfony
application and make it extend from the default `AdminController`. Then you
just add one or more methods in your controller to override the default ones.

The first step is to **create a new controller** anywhere in your Symfony
application. Its class name or namespace doesn't matter as long as it extends
the default `AdminController`:

```php
// src/AppBundle/Controller/AdminController.php
namespace AppBundle\Controller;

use JavierEguiluz\Bundle\EasyAdminBundle\Controller\AdminController as BaseAdminController;

class AdminController extends BaseAdminController
{
}
```

Then you must **update the routing configuration** to associate the `easyadmin`
route to the new controller. Open the `app/config/routing.yml` file and change
the `resource` option of the `easy_admin_bundle` route:

```yaml
# app/config/routing.yml
easy_admin_bundle:
    # resource: "@EasyAdminBundle/Controller/"           <-- REMOVE this line
    resource: "@AppBundle/Controller/AdminController.php" # <-- ADD this line
    type:     annotation
    prefix:   /admin
```

Save the changes and the backend will start using your own controller. Keep
reading the practical examples of the next sections to learn which methods you
can override in the controller.

#### Tweak the Default Actions for All Entities

This use case is only useful for very complex backends which need to override
the entire behavior of some default action. Define a method with the same name
of the view which you want to override (`list`, `search` or `show`):

```php
// src/AppBundle/Controller/AdminController.php
namespace AppBundle\Controller;

use JavierEguiluz\Bundle\EasyAdminBundle\Controller\AdminController as BaseAdminController;

class AdminController extends BaseAdminController
{
    public function listAction()   { ... }
    public function searchAction() { ... }
    public function showAction()   { ... }
}
```

Take a look at the code of these methods in the original `AdminController` or
extend from them to make your work easier.

#### Tweak the Default Actions for a Specific Entity

Before executing the general methods (`listAction()`, `showAction()`, etc.), the
controller looks for the existence of methods created for the current entity. In
particular, this is the syntax used to name these specific methods:

```php
public function list<EntityName>Action()   { ... }
public function search<EntityName>Action() { ... }
public function show<EntityName>Action()   { ... }
```

> **TIP**
>
> Given the syntax of method names, it's recommended to use CamelCase notation
> to set the entity names.

Suppose that you want to customize the behavior of the `list` view just for the
`Product` entity. Instead of overriding the general `listAction()` method and
checking for the right entity, is easier to define this method in your
controller:

```php
// ...
class AdminController extends BaseAdminController
{
    public function listProductAction()
    {
        // ...
    }
}
```

### Customization Based on Symfony Events

Lots of events are triggered during the execution of each backend action. Use
Symfony's event listeners or event subscribers and hook to these events to
modify the behavior of your backend.

EasyAdmin events are defined in the `EasyAdmin\Event\EasyAdminEvents` class
and these are the most relevant events for `list`, `search` and `show` views.

#### Initialization related events

The `initialize()` method is executed before any other action method
(`listAction()`, etc.) It checks for some common errors and initializes the
variables used by the rest of the methods (`$entity`, `$request`, `$config`,
etc.)

The two events related to this `initialize()` method are:

  * `PRE_INITIALIZE`, executed just at the beginning of the method, before any
    variable has been initialized and any error checked.
  * `POST_INITIALIZE`, executed at the very end of the method, just before
    executing the method associated with the current action.

#### Views related events

Each view defines two events which are dispatched respectively at the very
beginning of each method and at the very end of it, just before executing the
`$this->render()` instruction:

  * `PRE_LIST`, `POST_LIST`
  * `PRE_SEARCH`, `POST_SEARCH`
  * `PRE_SHOW`, `POST_SHOW`

#### The Event Object

Event listeners and subscribers receive an event object based on the
[GenericEvent class][2] defined by Symfony. The subject of the
event depends on the current action:

  * `show` action receives the current `$entity` object (this object is also
    available in the event arguments as `$event['entity']`).
  * `list` and `search` actions receive the `$paginator` object which contains
    the collection of entities that meet the criteria of the current listing
    (this object is also available in the event arguments as
    `$event['paginator']`).

In addition, the event arguments contain all the action method variables. You
can access to them through the `getArgument()` method or via the array access
provided by the `GenericEvent` class.

-------------------------------------------------------------------------------

&larr; [Chapter 2. Design Configuration](2-design-configuration.md)  |  [Chapter 4. Edit and New Views Configuration](4-edit-new-configuration.md) &rarr;

[1]: ../tutorials/custom-property-options.md
[2]: http://symfony.com/doc/current/components/event_dispatcher/generic_event.html
[3]: http://php.net/manual/en/function.date.php
[4]: http://php.net/manual/en/function.sprintf.php
