Advanced Design Customization
=============================

Customize the Templates of the Backend
--------------------------------------

In addition to loading your own stylesheets and scripts, you can also override
the templates used to build the backend interface. To do so, EasyAdmin defines
an advanced but simple to use overriding mechanism.

First, these are the names of the main templates which can be overridden:

  * `layout`, the common layout that decorates the rest of templates (`list`,
    `edit`, `new` and `show`);
  * `new`, the template used to render the page where new entities are created;
  * `show`, the template used to render the contents stored by a given entity;
  * `edit`, the template used to render the page where entities are edited;
  * `list`, the template used to render the listing page and the search
    results page;
  * `paginator`, the template used to render the paginator of the `list` and
    `search` views;
  * `form`, the template to render the form displayed in the `new` and `edit`
    pages.

EasyAdmin applies the following overriding mechanism to select the template
used to render each element (from highest to lowest priority):

  1. `easy_admin.entities.<entityName>.templates.<templateName>` configuration 
     option.
  2. `easy_admin.design.templates.<template_name>` configuration option.
  3. `app/Resources/views/easy_admin/<entity_name>/<template_name>.html.twig`
  4. `app/Resources/views/easy_admin/<template_name>.html.twig`
  5. `@EasyAdmin/default/<template_name>` (these are the templates defined by
     EasyAdmin and they are always available).

### Tweaking the default templates for all entities

Most often than not, customizing the design of the backend is a matter of
tweaking some element of the default templates. The easiest way to do that is
to create a new template that extends from the default one and override the
specific template block you want to customize.

Suppose you want to change the search form displayed in the `list` view.
First, create a new template in `app/Resources/views/easy_admin/list.html.twig`
and make it extend from the default `list` template. Then, override the
`search_action` block:

```twig
{# app/Resources/views/easy_admin/list.html.twig #}
{% extends '@EasyAdmin/default/list.html.twig' %}

{% block search_action %}
    {# ... #}
{% endblock %}
```

If you prefer to use one of your existing templates located elsewhere, add the
``easy_admin.design.templates.list` option and use any of the valid Symfony
formats to define the template path:

```yaml
easy_admin:
    design:
        templates:
            list: 'AppBundle:Backend:list.html.twig'
```

### Tweaking the default templates for some entities

Follow the steps explained in the previous section, but change the location of
the template to a subdirectory called after the entity. Suppose you want to
override the search form just for the `Customer` entity. Then, create this
template:

```
your-project/
├─ app/
│  ├─ ...
│  └─ Resources/
│     └─ views/
│        └─ easy_admin/
│           └─ Customer/
│              └─ list.html.twig
├─ src/
├─ vendor/
└─ web/
```

Again, you can use an existing template located elsewhere via the ``templates``
configuration option. This time you have to define the option just for the
specific entity:

```yaml
easy_admin:
    entities:
        Customer:
            # ...
            templates:
                list: 'AppBundle:Backend:list.html.twig'
```

### Overriding the default templates for all entities

Suppose you want to modify the paginator displayed at the bottom of each
listing page. This element is built with the `paginator` template, so you have
to create the following template to override it for all entities:

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

If you prefer to use one of your existing templates to override it, add the
``easy_admin.design.templates.paginator` option and use any of the valid
Symfony formats to define the template path:

```yaml
easy_admin:
    design:
        templates:
            paginator: 'AppBundle:Default:fragments/_paginator.html.twig'
```

### Overriding the default templates for some entities

Follow the steps explained in the previous section, but change the location of
the template to a subdirectory called after the entity. Suppose you want to
override the paginator just for the `Customer` entity. Then, create this
template:

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

Again, you can use an existing template located elsewhere via the ``templates``
configuration option. This time you have to define the option just for the
specific entity:

```yaml
easy_admin:
    entities:
        Customer:
            # ...
            templates:
                paginator: 'AppBundle:Default:fragments/_paginator.html.twig'
```

Customize the Templates Used to Render Each Field
-------------------------------------------------

You can use the same overriding mechanism to customize the template fragments
used to render each field type. These are the available templates (most of
them correspond to the associated Doctrine data type):

  * `field_array`
  * `field_association`, used to render fields that store Doctrine associations
  * `field_bigint`
  * `field_boolean`
  * `field_date`
  * `field_datetime`
  * `field_datetimetz`
  * `field_decimal`
  * `field_float`
  * `field_id`, used to render the field called `id`. This avoids formatting 
    its value as any other regular number with decimals and thousand separators
  * `field_image`, special type used to display the contents of an image
  * `field_integer`
  * `field_simple_array`
  * `field_smallint`
  * `field_string`
  * `field_text`
  * `field_time`
  * `field_toggle`, special type used to display boolean values as flip 
    switches
  * `label_empty`, used when the field to render is an empty collection
  * `label_inaccessible, used when is not possible to access the value of the
    field to render because there is no getter or public property
  * `label_null`, used when the value of the field to render is null
  * `label_undefined`, used when any kind of error or exception happens when
    trying to access the value of the field to render

Suppose that in you backend you don't want to display a `NULL` badge for null
values and prefer to display a more human friendly value, such as a dash (`-`).
The easiest way to override this template, would be to create a new
`label_null` template with your own contents:

```twig
{# app/Resources/views/easy_admin/label_null.html.twig #}
<span class="null">~</span>
```

To override this value just for a specific entity (for example, `Invoice`), 
create this other template:

```twig
{# app/Resources/views/easy_admin/Invoice/label_null.html.twig #}
<span class="null">Unpaid</span>
```

To use your own custom template, define the following configuration option:

```yaml
easy_admin:
    design:
        templates:
            label_null: 'AppBundle:Default:labels/null.html.twig'
```

If you want to customize any of these templates, it's recommended to check out
first the contents of the default `field_*` and `label_*` templates, so you can
learn about their features.

Inside the `field_*` and `label_*` templates you can use any of the following
variables:

  * `view`, the name of the view where the field is being rendered (`show` or
    `list`).
  * `value`, the content of the field being rendered, which can be a variable
    of any time (String, numeric, boolean, array, etc.)
  * `format`, available only for the date and numeric field types. It defines
    the formatting that should be applied to the value before displaying it.
