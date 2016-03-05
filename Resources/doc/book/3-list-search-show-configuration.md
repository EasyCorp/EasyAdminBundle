Chapter 3. List, Search and Show Views Configuration
====================================================

This chaper explains all the configuration options available for the `list`,
`search` and `show` views.

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

This section explains the configuration options that can be applied to `list`,
`search` and `show` views. The examples use the `list` view, but you can replace
it by `search` or `show`.

### Customize the Title of the Page

By default, page titles just display the name of the entity. Define the `title`
option to set a custom page title:

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

The `title` content can include the following special variables:

  * `%entity_label%`, resolves to the value defined in the `label` option of
    the entity. If you haven't defined it, this value will be equal to the
    entity name. In the example above, this value would be `Customers`.
  * `%entity_name%`, resolves to the entity name, which is the YAML key used
    to configure the entity in the backend configuration file. In the example
    above, this value would be `Customer`.
  * `%entity_id%`, resolves to the value of the primary key of the entity being
    edited or showed. This variable is only available for the `show` view. Even
    if the option is called `entity_id`, it also works for primary keys with
    names different from `id`.

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

### Customize the Number of Item Rows Displayed

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

Properties are displayed by default with the most appropriate appearance
according to their data types. Besides, their labels are generated automatically
based on their property name (e.g. if the property name is `published`, the
label will be `Published` and if the name is `dateOfBirth`, the label will be
`Date of birth`).

In order to customize the appearance of the properties, use the following
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

Instead of using a string to define the name of the property (e.g. `email`) you
have to define a hash with the name of the property (`property: 'email'`) and
the custom label you want to display (`label: 'Contact'`).

If your view contains lots of properties and most of them define their own
custom label, consider using the alternative YAML syntax for sequences to
improve the legibility of your backend configuration. The following example is
equivalent to the above example:

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

  * `property` (mandatory): the name of the Doctrine property which you want to
    display. Properties can be real (they exist as Doctrine properties) or
    "virtual" (they just define getter/setter methods, as explained later). The
    `property` option is the only mandatory option when using the extended
    field configuration format.
  * `label` (optional): the title displayed for the property. The default
    title is the "humanized" version of the property name (e.g. 'fieldName' is
    transformed into 'Field name').
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

The `show` view defines another two options:

  * `help` (optional): the help message displayed below the field.
  * `css_class` (optional): the CSS class applied to the field container element.

Virtual Properties
------------------

Sometimes, it's useful to display values which are not entity properties. For
example, if your `Customer` entity defines the `firstName` and `lastName`
properties, you may want to just display a column called `Name` with both
values merged. These are called *virtual properties* because they don't really
exist as Doctrine entity properties.

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

Custom Field Data Types
-----------------------

### Toogle and Boolean Data Types

If an entity is editable, the `list` view displays its boolean properties as
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
the rendered HTML content. In case you want to display the rendered content, set
the type of the property to `raw`:

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

EasyAdmin uses the following seven Twig templates to create its interface:

  * `layout`, the common layout that decorates the `list`, `edit`, `new` and
    `show` templates;
  * `new`, renders the page where new entities are created;
  * `show`, renders the contents stored by a given entity;
  * `edit`, renders the page where entity contents are edited;
  * `list`, renders the entity listings and the search results page;
  * `paginator`, renders the paginator of the `list` view;
  * `form`, renders the form of the `new` and `edit` views.

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

If you prefer to define the custom templates in some specific location of your
application, it's more convenient to use the `templates` option to define their
location.

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
  * `field_toggle.html.twig`, related to the special `toggle` type defined by
    EasyAdmin for boolean properties.

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
            # label_null: '@App/Default/labels/null.html.twig'
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

### Customizing the Template Used to Render Each Property

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

### Adding Custom Logic to Property Templates

All property templates receive a parameter called `field_options` with the full
list of options defined in the configuration file for that property. If you
add custom options, they will also be available in the `field_options`
parameter. This allows you to add custom logic to templates very easily.

Imagine that you want to translate some text contents in the `list` view. To do
so, define a custom option called `trans` which indicates if the property
content should be translated and another option called `domain` which defines
the name of the translation domain to use.

```yaml
# app/config.yml
Product:
    class: AppBundle\Entity\Product
    label: 'Products'
    list:
        fields:
            - id
            - { property: 'name', trans: true, domain: 'messages' }
            # ...
```

Supposing that the `name` property is of type `string`, you just need to
override the built-in `field_string.html.twig` template:

```twig
{# app/Resources/views/easy_admin/field_string.html.twig #}

{% if field_options.trans|default(false) %}
    {# translate fields defined as "translatable" #}
    {{ value|trans({}, field_options.domain|default('messages')) }}
{% else %}
    {# if not translatable, simply include the default template #}
    {{ include('@EasyAdmin/default/field_string.html.twig') }}
{% endif %}
```

If the custom logic is too complex, it may be better to use your own custom
template to not mess built-in templates too much. In this example, the
collection of tags associated with a product is displayed in a way that is too
customized to use a built-in template:

```yaml
# app/config.yml
Product:
    class: AppBundle\Entity\Product
    label: 'Products'
    list:
        fields:
            - id
            # ...
            - { property: 'tags', template: 'tag_collection', label_colors: ['primary', 'success', 'info'] }
```

The custom `tag_collection.html.twig` would look as follows:

```twig
{# app/Resources/views/easy_admin/tag_collection.html.twig #}

{% set colors = field_options.label_colors|default(['primary']) %}

{% for tag in value %}
    <span class="label label-{{ cycle(colors, loop.index) }}">{{ tag }}</span>
{% endfor %}
```

And this property would be rendered in the `list` view as follows:

![Default listing interface](../images/easyadmin-design-customization-custom-data-types.png)


Customizing the Controllers
---------------------------

### Sutom Admin Controller

### Events

