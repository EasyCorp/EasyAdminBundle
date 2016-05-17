Chapter 2. Design Configuration
===============================

The design of the backend can be customized in two ways:

  1. Defining some global YAML configuration options, which is enough for simple
     backends (as explained in this chapter).
  2. Overriding the default templates and fragments used to render the backend,
     which is useful for very complex backends (as explained in the following
     chapters).

General design options are defined under the `design` YAML key.

Changing the Main Backend Color
-------------------------------

Define the `brand_color` option to change the default blue color used by the
backend interface:

```yaml
easy_admin:
    design:
        brand_color: '#1ABC9C'
    # ...
```

![Using a custom brand color in the backend](../images/easyadmin-design-brand-color.png)

The value of the `brand_color` option can use any of the valid CSS color formats:

```yaml
easy_admin:
    design:
        brand_color: 'red'
        brand_color: 'rgba(26, 188, 156, 0.85)'
        brand_color: 'hsl(0, 100%, 50%);'
    # ...
```

Changing the Color Scheme
-------------------------

By default, backend interface uses a dark color scheme, which is the most common
choice for admin applications. If you prefer a lighter alternative, add the
`color_scheme` option with the `light` value:

```yaml
easy_admin:
    design:
        # 'dark' is the default value
        color_scheme: 'light'
    # ...
```

![The default backend homepage using the light color scheme](../images/easyadmin-design-color-scheme-light.png)

Adding Custom Web Assets
------------------------

Complex backends may require to load custom CSS and JavaScript files. Add the
`assets` option to define the paths of the web assets to load in the backend
layout. All kinds of assets are supported and linked accordingly:

```yaml
easy_admin:
    design:
        assets:
            css:
                # HTTP protocol-relative URL
                - '//example.org/css/admin1.css'
                # absolute non-secure URL
                - 'http://example.org/css/admin2.css'
                # absolute secure URL
                - 'https://example.org/css/admin3.css'
                # absolute internal bundle URL
                - '/bundles/acmedemo/css/admin4.css'
                # relative internal bundle URL
                - 'bundles/app/css/admin5.css'
            js:
                # this option works exactly the same as the 'css' option
                - '//example.org/js/admin1.js'
                - 'http://example.org/js/admin2.js'
                - 'https://example.org/js/admin3.js'
                - '/bundles/acmedemo/js/admin4.js'
                - 'bundles/app/js/admin5.js'
    # ...
```

### CSS Selectors

Every backend page applies some `id` and `class` attributes to its `<body>`
element. The `id` attribute allows you to target specific entity instances and
its value follows this pattern:

| View   | `<body>` ID attribute
| ------ | --------------------------------------------------------------------
| `edit` | `easyadmin-edit-<entity_name>-<entity_id>`
| `list` | `easyadmin-list-<entity_name>`
| `new`  | `easyadmin-new-<entity_name>`
| `show` | `easyadmin-show-<entity_name>-<entity_id>`

The `class` attribute allows you to target entire sections of the backend and
their values follow these patterns:

| View   | `<body>` CSS class
| ------ | --------------------------------------------------------------------
| `edit` | `easyadmin edit edit-<entity_name>`
| `list` | `easyadmin list list-<entity_name>`
| `new`  | `easyadmin new new-<entity_name>`
| `show` | `easyadmin show show-<entity_name>`

Changing the favicon
--------------------

A nice trick for backends is to change their favicon to better differentiate
the backend from the public website (this is specially useful when opening lots
of tabs in your browser).

If you want to apply this technique to your backends, create the favicon image
(using any common format: `.ico`, `.png`, `.gif`, `.jpg`) and set the `favicon`
option:

```yaml
easy_admin:
    design:
        assets:
            favicon: '/assets/backend/favicon.png'
    # ...
```

The value of the `favicon` option is used as the value of the `href` attribute
of the `<link rel="icon">` element in the backend's layout.

If your favicon uses an uncommon graphic format, you must define both the `path`
of the favicon and its `mime_type`:

```yaml
easy_admin:
    design:
        assets:
            favicon:
                path: '/assets/backend/favicon.xxx'
                mime_type: 'image/xxx'
    # ...
```

-------------------------------------------------------------------------------

&larr; [Chapter 1. General Configuration](1-general-configuration.md)  |  [Chapter 3. List, Search and Show Views Configuration](3-list-search-show-configuration.md) &rarr;
