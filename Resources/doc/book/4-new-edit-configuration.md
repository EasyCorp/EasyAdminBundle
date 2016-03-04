Chapter 4. New and Edit Views Configuration
===========================================

The **Edit and New Views** are pretty similar. The `new` view is used when
creating new elements of the given entity. The `edit` view is displayed when
modifying the contents of any existing entity:

![Edit view interface](../images/easyadmin-edit-view.png)


Common View Configuration
-------------------------

In this section you'll learn about the configuration options that can be applied
to all views. The examples will use the `list` view, but you can replace it
with any other view (`edit`, `new`, `show`, `search`). In the following sections
you'll learn the specific configuration options available for each view.

### Customize the Title of the Page

By default, titles just display the name of the entity. Define the `title`
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
    edited or showed. This variable is only available for the `show` and `edit`
    views. Even if the option is called `entity_id`, it also works for primary
    keys with names different from `id`.

Beware that, in Symfony applications, YAML values enclosed with `%` and `%` have
a special meaning (they are considered container parameters). Escape these
variables by doubling the `%` characters:

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

### Customize the Properties Displayed

By default, the `edit`, `new` and `show` views display all the entity properties.
The `list` and `search` views make some "smart guesses" to decide which columns
to display to make listings look good.

Use the `fields` option to explicitly set the properties to display in each
view:

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

This option is useful to reorder the way properties are displayed. By default
properties are displayed in the same order as defined in the related Doctrine
entity.

If any of the properties is an association with another entity, the `edit` and
`new` views render it as a `<select>` list. The elements displayed in this list
are the values returned by the `__toString()` PHP method. Define this method
in all your entities to avoid errors and to define the textual representation
of the entity.

### Virtual Properties

Sometimes, it's useful to display values which are not entity properties. For
example, if your `Customer` entity defines the `firstName` and `lastName`
properties, you may want to just display a column called `Name` with both
values merged. These are called *virtual properties* because they don't really
exist as Doctrine entity properties.

The first step to use a virtual property is to add it to the entity configuration
as any other property:

```yaml
easy_admin:
    entities:
        Customer:
            class: AppBundle\Entity\Customer
            list:
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
property. Virtual properties also work for the `edit` and `new` views, as long
as you define a *setter* method for them (`setName()` in the example above).

By default, virtual properties are displayed as text contents. If your virtual
property is a *boolean* value or a date, use the `type` option to set a more
appropriate data type:

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

Customize the Properties Appearance
-----------------------------------

By default, properties are displayed with the most appropriate appearance
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
                    - 'id'
                    - 'name'
                    - { property: 'email', label: 'Contact' }
    # ...
```

These are the options that you can define for each field:

  * `property` (mandatory): the name of the Doctrine property which you want to
    display (in `list`, `search` and `show` views), set (in `new` view) or
    modify (in `edit` view). Properties can be real (they exist as Doctrine
    properties) or "virtual" (they just define getter/setter methods). The
    `property` option is the only mandatory option when using the extended
    field configuration format.
  * `label` (optional): the title displayed for the property. The default
    title is the "humanized" version of the property name
    (e.g. 'fieldName' is transformed into 'Field name').
  * `help` (optional): the help message displayed below the form field in the
    `edit`, `new` and `show` views.
  * `css_class` (optional): the CSS class applied to the form field widget
    container element in the `edit`, `new` and `show` views. For example, when
    using the default Bootstrap based form theme, this value is applied to the
    `<div>` element which wraps the label, the widget and the error messages of
    the field.
  * `template` (optional): the name of the custom template used to render the
    contents of the field in the `list` and `show` views. This option is fully
    explained in the [Advanced Design Customization] [advanced-design-customization] tutorial.
  * `type` (optional): the type of data displayed in the `list`, `search` and
    `show` views and the form widget displayed in the `edit` and `new` views.
    These are the supported types:
    * All the [Symfony Form types](http://symfony.com/doc/current/reference/forms/types.html)
    * Custom EasyAdmin types:
      * `image`, displays inline images in the `list`, `search` and `show` views
        (as explained later in this chapter).
      * `toggle`, displays a boolean value as a flip switch in the `list` and
        `search` views (as explained later in this chapter).
      * `raw`, displays the value unescaped (using the `raw` Twig filter), which
        is useful when the content stores HTML code that must be rendered
        instead of displayed as HTML tags (as explained later in this chapter).
  * `type_options` (optional), a hash which defines the value of any of the
    valid options defined by the Symfony Form type associated with the field.

The `type_options` is the most powerful option because it literally comprises
tens of options suited for each form type:

```yaml
easy_admin:
    entities:
        Customer:
            class: AppBundle\Entity\Customer
            form:
                fields:
                    - 'id'
                    - { property: 'email', type: 'email', type_options: { trim: true } }
                    - { property: 'interests', type_options: { expanded: true, multiple: true } }
                    - { property: 'updated_at', type_options: { widget: 'single_text' } }
```

Read the [Symfony Form type reference](http://symfony.com/doc/current/reference/forms/types.html)
to learn about all the available options, their usage and allowed values.

> In addition to these options defined by EasyAdmin, you can define any custom
> option for the fields. This way you can create very powerful backend
> customizations, as explained in the
> [Advanced Design Customization] [advanced-design-customization] tutorial.


Customizing Form Design
-----------------------

By default, forms are displayed using the **horizontal style** defined by the
Bootstrap 3 CSS framework:

![Default horizontal form style](../images/easyadmin-form-horizontal.png)

The style of the forms can be changed application-wide using the `form_theme`
option inside the `design` configuration section. In fact, the default form
style is equivalent to using this configuration:

```yaml
easy_admin:
    design:
        form_theme: 'horizontal'
    # ...
```

If you prefer to display your forms using the **vertical Bootstrap style**,
change the value of this option to `vertical`:

```yaml
easy_admin:
    design:
        form_theme: 'vertical'
    # ...
```

The same form shown previously will now be rendered as follows:

![Vertical form style](../images/easyadmin-form-vertical.png)

The `horizontal` and `vertical` values are just nice shortcuts for the two
built-in form themes. But you can also use your own form themes. Just set the
full theme path as the value of the `form_theme` option:

```yaml
easy_admin:
    design:
        form_theme: '@AppBundle/form/custom_layout.html.twig'
    # ...
```

You can even pass several form themes in an array to use all of them when
rendering the backend forms:

```yaml
easy_admin:
    design:
        form_theme:
            - '@AppBundle/form/custom_layout.html.twig'
            - 'form_div_layout.html.twig'
    # ...
```

### Multiple-Column Forms

EasyAdmin doesn't support multi-column form layouts. However, you can use the
`css_class` form field to create these advanced layouts. The `css_class` value
is applied to the parent `<div>` element which contains the field label, the
field widget, the field help and the optional field errors:

![Multi-column form](../images/easyadmin-form-multi-column.png)

The configuration used to display this form is the following:

 ```yaml
easy_admin:
    design:
        form_theme: 'vertical'
    entities:
        Product:
            # ...
            form:
                fields:
                    - { property: name, css_class: 'col-sm-12' }
                    - { property: price, type: 'number', help: 'Prices are always in euros', css_class: 'col-sm-6' }
                    - { property: 'ean', label: 'EAN', help: 'EAN 13 valid code. Leave empty if unknown.', css_class: 'col-sm-6' }
                    - { property: 'enabled', css_class: 'col-sm-12' }
                    - { property: 'description', css_class: 'col-sm-12' }
    # ...
```




Edit and New Views Configuration
--------------------------------

### The Special Form View

The `edit` and `new` views are pretty similar, so most of the times you apply
the same customization to them. Instead of duplicating the configuration for
both views, you can use the special `form` view:

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
`form` action to define the common configuration, and then use the `new` and
`edit` views to define just the specific options you want to override:

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

### Custom Doctrine Types

When your application defines custom Doctrine DBAL types, you must define a
related custom form type before using them as form fields. Imagine that your
application defines a `UTCDateTime` type to convert the timezone of datetime
values to UTC before saving them in the database.

If you add that type in a property as follows, you'll get an error message
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

This problem is solved defining a custom `utcdatetime` Form Type. Read the
[How to Create a Custom Form Field Type](http://symfony.com/doc/current/cookbook/form/create_custom_field_type.html)
article of the official Symfony documentation to learn how to define custom
form types.

### Applying Custom Options for Entity Forms

By default, EasyAdmin only sets the `data_class` option in the forms built to
create and edit entities. If you need to pass custom options to any form, define
the `form_options` option under the `edit`, `new` or `form` options:

```yaml
easy_admin:
    entities:
        Customer:
            class: AppBundle\Entity\Customer
            form:
                form_options: { validation_groups: ['Default', 'my_validation_group'] }
    # ...
```

This configuration makes EasyAdmin to generate the form for the `edit` and `new`
views of the `Customer` entity using this PHP code:

```php
$form = $this->createFormBuilder($entity, array(
    'data_class' => 'AppBundle\Entity\Customer',
    'validation_groups' => array('Default', 'my_validation_group'),
))
-> ...
```
