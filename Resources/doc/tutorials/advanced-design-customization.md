Advanced Design Customization
=============================

This article explains how to completely customize the backend design by
overriding the default templates and fragments used to render the interface.

Customize the Templates Used by the Backend
-------------------------------------------

EasyAdmin uses the following seven Twig templates to create its interface:

  * `layout`, the common layout that decorates the `list`, `edit`, `new` and
    `show` templates;
  * `new`, renders the page where new entities are created;
  * `show`, renders the contents stored by a given entity;
  * `edit`, renders the page where entity contents are edited;
  * `list`, renders the entity listings and the search results page;
  * `paginator`, renders the paginator of the `list` view;
  * `form`, renders the form of the `new` and `edit` views.

EasyAdmin offers a powerful overriding mechanism which allows you to customize
any of these templates in several different ways. Depending on your needs you
must select the best alternative.

Before rendering a template, EasyAdmin applies the following logic to choose
the template (the first existing template is used):

  1. The template defined in the
     `easy_admin.entities.<EntityName>.templates.<TemplateName>` configuration
     option.
  2. The template defined in the `easy_admin.design.templates.<TemplateName>`
     configuration option.
  3. `app/Resources/views/easy_admin/<EntityName>/<TemplateName>.html.twig`
     template.
  4. `app/Resources/views/easy_admin/<TemplateName>.html.twig`
     template.
  5. `@EasyAdmin/default/<TemplateName>.html.twig` (these are the default
     templates defined by EasyAdmin and they are always available).

The following sections explain all these alternatives with practical examples.

### Tweaking the Default Templates for All Entities

Most often than not, customizing the design of the backend is a matter of just
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

### Tweaking the Default Templates for Some Entities

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

### Overriding the Default Templates for All Entities

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

### Overriding the Default Templates for Some Entities

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

The same template overriding mechanism can be applied to customize the template
fragments used to render each property in the `list`, `search` and `show` views
(if you need to customize the form fields, configure your own form theme as
explained in the [chapter 5] [chapter-5] of the "Getting Started" guide).

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
    used to display the contents of an image.
  * `field_integer`
  * `field_simple_array`
  * `field_smallint`
  * `field_string`
  * `field_text`
  * `field_time`
  * `field_toggle`, related to the special `toggle` type defined by EasyAdmin
    used to display boolean values as flip switches.
  * `label_empty`, used when the property to render is an empty collection.
  * `label_inaccessible`, used when is not possible to access the value of the
    property because there is no getter or public property.
  * `label_null`, used when the value of the property is null.
  * `label_undefined`, used when any kind of error or exception happens when
    trying to access the value of the property.

Suppose that in your backend you don't want to display a `NULL` text for `null`
values and prefer to display a more human friendly value, such as a dash (`-`).
Making this change is as easy as creating a new `label_null` template with your
own content and HTML markup:

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

To use an existing template located elsewhere, define the global
`design.templates` configuration option or the entity's `templates` option
depending on your needs:

```yaml
easy_admin:
    design:
        templates:
            label_null: 'AppBundle:Default:labels/null.html.twig'
    # ...
    entities:
      Invoice:
        templates:
            label_null: 'AppBundle:Invoice:backend/label_null.html.twig'
```

Before customizing any of these templates, it's recommended to check out the
contents of the default `field_*` and `label_*` templates, so you can learn
about their features.

Inside the `field_*` and `label_*` templates you have access to the following
variables:

  * `field_options`, the options configured for this field in the backend
    configuration file.
  * `item`, the entity instance.
  * `value`, the content of the field being rendered, which can be a variable
    of any type (string, numeric, boolean, array, etc.)
  * `view`, the name of the view where the field is being rendered (`show` or
    `list`).

Therefore, you can do almost anything by overriding the templates used to render a property.

### Add custom logic to existing dataTypes:
  
For instance, you might want to translate your entities properties:

```yaml
# app/config.yml
Product:
    class: AppBundle\Entity\Product
    label: 'Products'
    list:
        fields:
            - id
            # Make the "name" property translatable on "list" view by adding our own options:
            - { property: 'name', translatable: { domain: 'messages', placeholders: { ... } } }
            # ...
```

Override the `string` data type template:

```twig
{# app/Resources/views/easy_admin/field_string.html.twig #}

{# Check if the field is defined as translatable #}
{% if field_options.translatable is defined %}
    {% set trans_options = {placeholders: {}, domain: null}|merge(field_options.translatable) %}
    {# translate the property value using our custom options #}
    {{ value|trans(trans_options.placeholders, trans_options.domain) }}
{% else %}
    {# if not translatable, simply include the default template #}
    {{ include('@EasyAdmin/default/field_string.html.twig') }}
{% endif %}
```

### Declare and use a custom data type on-the-fly

You can also declare on-the-fly your own data types ! Easyadmin will detect and try to render them using the same template overriding mechanism used to render each classic property :

```yaml
# app/config.yml
Product:
    class: AppBundle\Entity\Product
    label: 'Products'
    list:
        fields:
            - id
            # ...
            - { property: 'tags', type: 'tag_collection', type_options: { labels_cycle: ['primary', 'success', 'info'] } }
```

Define the default template to use:

```twig
{# app/Resources/views/easy_admin/field_tag_collection.html.twig #}

{% set default_type_options = { labels_cycle: ['primary'] } %}
{% set type_options = default_type_options|merge(fieldMetadata.type_options|default({})) %}

{% for tag in value %}
    <span class="label label-{{ cycle(type_options.labels_cycle, loop.index) }}">{{ tag }}</span>
{% endfor %}
```

![Default listing interface](/Resources/doc/images/easyadmin-design-customization-custom-data-types.png)

[chapter-5]: ../getting-started/5-design-customization.md
