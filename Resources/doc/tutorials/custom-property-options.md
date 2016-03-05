How to Define Custom Options for Entity Properties
==================================================

This article explains how to define custom options for any entity property and
how to use those options in the `list`, `search` or `show` views.

Default Property Options
------------------------

Properties included in the `fields` option of any entity can define several
options (`property`, `label`, `template`, `type`, `help` and `css_class`):

```yaml
# app/config.yml
easy_admin:
    entities:
        User:
            class: AppBundle\Entity\User
            list:
                fields:
                    - { property: 'email', label: 'Contact' }
                    - { property: 'biography', help: 'Markdown allowed' }
                    # ...
```

Custom Property Options
-----------------------

Adding custom options is as simple as defining their names and values in each
property configuration. Imagine that you want to translate the contents of a
property called `name` in the `list` view. To do so, define a custom option
called `trans` which indicates if the property should be translated and another
option called `domain` which defines the name of the translation domain to use:

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

Using Custom Property Options in Templates
------------------------------------------

Property templates receive a parameter called `field_options` which is an array
that contains all the options defined in the configuration file for that
property. If you add custom options, they will also be available in that
`field_options` parameter. This allows you to add custom logic to templates very
easily.

Considering that the `name` property is of type `string`, override the built-in
`field_string.html.twig` templateto add support for the `trans` and `domain`
options:

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

If the custom logic is too complex, it may be better to render the property with
its own custom template to not mess the default templates too much. In the
following example, the backend wants to display a collection of tags with the
colors configured for the property.

Since this business logic is too specific, it's better to not reuse the
corresponding default template. The solution is to define a custom template just
for this property and make use of the `label_colors` custom option:

```yaml
# app/config.yml
easy_admin:
    entities:
        Product:
            class: AppBundle\Entity\Product
            list:
                fields:
                    # ...
                    - { property: 'tags', template: 'tag_collection.html.twig',
                        label_colors: ['primary', 'success', 'info'] }
```

The custom `tag_collection.html.twig` template would look as follows:

```twig
{# app/Resources/views/easy_admin/tag_collection.html.twig #}

{% set colors = field_options.label_colors|default(['primary']) %}

{% for tag in value %}
    <span class="label label-{{ cycle(colors, loop.index) }}">{{ tag }}</span>
{% endfor %}
```

And this property would be rendered in the `list` view as follows:

![Default listing interface](../images/easyadmin-design-customization-custom-data-types.png)
