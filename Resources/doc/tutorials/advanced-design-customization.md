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

s