Advanced Design Customization
=============================

This article explains how to completely customize the backend design by
overriding the default templates and fragments used to render the interface.

Customize the Templates Used by the Backend
-------------------------------------------

EasyAdmin uses the following seven Twig templates to create its interface:

  * `layout`, the common layout that decorates the rest of templates (`list`,
    `edit`, `new` and `show`);
  * `new`, used to render the page where new entities are created;
  * `show`, used to render the contents stored by a given entity;
  * `edit`, used to render the page where entities are edited;
  * `list`, used to render the listings and the search results page;
  * `paginator`, used to render the paginator of the `list` and `search` views;
  * `form`, used to render the form of the `new` and `edit` views.

EasyAdmin offers a powerful overriding mechanism which allows you to customize
any of these templates in several different ways. Depending on your needs you
must select the best alternative.

Before rendering a template, EasyAdmin applies the following logic to decide
which template to choose (the first existing template is used):

  1. The template defined in the
     `easy_admin.entities.<entityName>.templates.<templateName>` configuration
     option.
  2. The template defined in the `easy_admin.design.templates.<template_name>`
     configuration option.
  3. `app/Resources/views/easy_admin/<entity_name>/<template_name>.html.twig`
     template.
  4. `app/Resources/views/easy_admin/<template_name>.html.twig`
     template.
  5. `@EasyAdmin/default/<template_name>.html.twig` (these are the default
     templates defined by EasyAdmin and they are always available).

The following sections explain all these alternatives with practical examples.

### Tweaking the Default Templates for All Entities

Most often than not, customizing the design of the backend is a matter of
tweaking some element of the default templates. The easiest way to do that is
to create a new template that extends from the default one and override the
specific Twig block you want to customize.

Suppose you want to change the search form of the `list` view for all entities.
First, create a new `list.html.twig` template in this location:

```
your-project/
├─ app/
│  ├─ ...
│  └─ Resources/
│     └─ views/
│        └─ easy_admin/
│           └─ list.html.twig
├─ src/
├─ vendor/
└─ web/
```

Then, make your template extend from the default `list` template:

```twig
{# app/Resources/views/easy_admin/list.html.twig #}
{% extends '@EasyAdmin/default/list.html.twig' %}

{# ... #}
```

Lastly, override the `search_action` block to just change that template part:

```twig
{# app/Resources/views/easy_admin/list.html.twig #}
{% extends '@EasyAdmin/default/list.html.twig' %}

{% block search_action %}
    {# ... #}
{% endblock %}
```

Creating a template in `app/Resources/views/easy_admin/` is a convention which
simplifies the overriding of templates. If you prefer to use an existing
template located elsewhere, define the `easy_admin.design.templates.list`
option and use any of the valid Symfony formats to define the template path:

```yaml
easy_admin:
    design:
        templates:
            list: 'AppBundle:Backend:list.html.twig'
```

### Tweaking the default templates for some entities

In this case, the changes are applied just for one entity, instead of applying
them in the entire backend. To do so, follow the steps explained in the
previous section, but change the location of the template to a subdirectory
called after the entity name.

Suppose you want to override the search form just for the `Customer` entity.
Then, create this `list.html.twig` template:

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

Again, you can use an existing template located elsewhere via the `templates`
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

Default templates define lots of Twig blocks to provide you great flexibility.
However, sometimes it's not enough to modify the default templates and you need
to change them completely. The solution is to follow the same steps explained
in the previous sections, but without extending your templates from the
default ones.

Suppose you want to modify the paginator displayed at the bottom of each
listing page. This element is built with the `paginator` template, so you have
to create a new `paginator.html.twig` template in this location:

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

If you prefer to use one of your existing templates to override it, define the
`easy_admin.design.templates.paginator` option and use any of the valid
Symfony formats to set the template path:

```yaml
easy_admin:
    design:
        templates:
            paginator: 'AppBundle:Default:fragments/_paginator.html.twig'
```

### Overriding the default templates for some entities

Similarly, you can also replace the paginator template just for a single
entity. For example, if you want to override the paginator of the `Customer`
entity, create this template:

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

Again, you can use an existing template located elsewhere via the `templates`
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

Customize the Templates Used to Render Each Property
----------------------------------------------------

You can use the same overriding mechanism to customize the template fragments
used to render each property in the `list`, `search` and `show` views. If you
want to customize the form fields, configure your own form theme as explained
in the [chapter 5] [chapter-5] of the "Getting Started" guide.

These are the available templates (most of them correspond to the associated
Doctrine data type and are self-explanatory):

  * `field_array`
  * `field_association`, renders properties that store Doctrine associations.
  * `field_bigint`
  * `field_boolean`
  * `field_date`
  * `field_datetime`
  * `field_datetimetz`
  * `field_decimal`
  * `field_float`
  * `field_id`, special template to render any property called `id`. This
    avoids formatting the value of the primary key as a numeric value, with
    decimals and thousand separators.
  * `field_image`, related to the special `image` type defined by EasyAdmin
    to display the contents of an image.
  * `field_integer`
  * `field_simple_array`
  * `field_smallint`
  * `field_string`
  * `field_text`
  * `field_time`
  * `field_toggle`, related to the special `toggle` type defined by EasyAdmin
    to display boolean values as flip switches.
  * `label_empty`, used when the property to render is an empty collection.
  * `label_inaccessible`, used when is not possible to access the value of the
    property because there is no getter or public property.
  * `label_null`, used when the value of the property is null.
  * `label_undefined`, used when any kind of error or exception happens when
    trying to access the value of the property.

Suppose that in you backend you don't want to display a `NULL` text for `null`
values and prefer to display a more human friendly value, such as a dash (`-`).
To achieve it, create a new `label_null` template with your own content and
HTML markup:

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

Inside the `field_*` and `label_*` templates you have access to the following
variables:

  * `view`, the name of the view where the field is being rendered (`show` or
    `list`).
  * `value`, the content of the field being rendered, which can be a variable
    of any type (string, numeric, boolean, array, etc.)
  * `format`, available only for the date and numeric field types. It defines
    the formatting that should be applied to the value before displaying it.

[chapter-5]: ../getting-started/5-design-customization.md
