Twig Components
===============

EasyAdmin uses `Twig Components`_ to render many parts of its interface, such
as buttons, badges, icons, modal windows and dropdown menus. These components
are registered under the ``ea:`` prefix and you can also use them in your own
templates (e.g. when overriding backend templates or creating custom admin pages).

This appendix is a practical guide to those components. It doesn't describe
every option of every component; instead, it shows how to accomplish the most
common tasks with each of them.

All components share the same behavior for passing options ("props"): they are
passed as regular HTML attributes, and boolean props are enabled by adding the
attribute name without any value:

.. code-block:: twig

    <twig:ea:Alert variant="warning" title="{{ alert_title }}" withDismissButton>
        Some important message.
    </twig:ea:Alert>

In addition, any other attribute added to a component (``class``, ``id``,
``data-*``, etc.) is merged into the attributes of its root HTML element:

.. code-block:: twig

    <twig:ea:Badge variant="success" class="product-status" data-status="published">
        Published
    </twig:ea:Badge>

ActionMenu
----------

Renders a dropdown menu of related actions, like the one that displays the
entity actions and the :ref:`groups of actions <actions-grouping>` on the
index page. Build the menu by combining its subcomponents: a ``Button`` that
toggles the dropdown, an ``Overlay`` that wraps the menu contents, and an
``ActionList`` with the menu items:

.. code-block:: twig

    <twig:ea:ActionMenu>
        <twig:ea:ActionMenu:Button>
            <twig:ea:Icon name="internal:dots-horizontal"/>
        </twig:ea:ActionMenu:Button>

        <twig:ea:ActionMenu:Overlay>
            <twig:ea:ActionMenu:ActionList>
                <twig:ea:ActionMenu:ActionList:Item label="Edit" icon="internal:edit" url="/products/24/edit"/>
                <twig:ea:ActionMenu:ActionList:Item label="Duplicate" url="/products/24/duplicate"/>
                <twig:ea:ActionMenu:ActionList:Divider/>
                <twig:ea:ActionMenu:ActionList:Item label="Delete" icon="internal:delete" url="/products/24/delete" renderAsForm/>
            </twig:ea:ActionMenu:ActionList>
        </twig:ea:ActionMenu:Overlay>
    </twig:ea:ActionMenu>

Items render a link that performs a ``GET`` request by default. For actions
that modify data (like the "Delete" item above), add the ``renderAsForm`` prop
to submit the item URL with a ``POST`` request instead.

Use the ``Header`` subcomponent to add titles to groups of items, and the
``Content`` subcomponent to insert any custom markup in the menu:

.. code-block:: twig

    <twig:ea:ActionMenu:ActionList>
        <twig:ea:ActionMenu:ActionList:Header label="Download" icon="internal:download"/>
        <twig:ea:ActionMenu:ActionList:Item label="As HTML" url="/reports/download?type=html"/>
        <twig:ea:ActionMenu:ActionList:Item label="As PDF" url="/reports/download?type=pdf"/>
        <twig:ea:ActionMenu:ActionList:Divider/>
        <twig:ea:ActionMenu:ActionList:Content>
            <small class="px-3">Reports are rebuilt early every morning.</small>
        </twig:ea:ActionMenu:ActionList:Content>
    </twig:ea:ActionMenu:ActionList>

Menu items can also open a nested submenu. This is what EasyAdmin renders for
each :ref:`group of actions <actions-grouping>` of the index page, using the
``ItemGroup`` subcomponent. It expects the action group object in its ``group``
prop (it doesn't compose the submenu from individual items), so it's only
useful in templates rendered by EasyAdmin. When the group defines a main
action, the item is rendered as a split button: clicking on the label runs the
main action and clicking on the arrow opens the submenu.

.. code-block:: twig

    <twig:ea:ActionMenu:ActionList hasSubmenus>
        {% for item in entity.actions %}
            {% if item.isActionGroup %}
                <twig:ea:ActionMenu:ActionList:ItemGroup group="{{ item }}" entity="{{ entity }}"/>
            {% endif %}
        {% endfor %}
    </twig:ea:ActionMenu:ActionList>

Selectable Items
~~~~~~~~~~~~~~~~

Menus can also include selectable options, rendered with a trailing checkmark.
Wrap the items with ``RadioList`` (options are mutually exclusive) or
``CheckboxList`` (multiple options can be selected at the same time) and pass
the ``selected`` prop to each item:

.. code-block:: twig

    <twig:ea:ActionMenu:ActionList>
        <twig:ea:ActionMenu:ActionList:RadioList label="Theme">
            <twig:ea:ActionMenu:ActionList:Item label="Light" url="?theme=light" selected="{{ theme == 'light' }}"/>
            <twig:ea:ActionMenu:ActionList:Item label="Dark" url="?theme=dark" selected="{{ theme == 'dark' }}"/>
        </twig:ea:ActionMenu:ActionList:RadioList>
    </twig:ea:ActionMenu:ActionList>

.. note::

    These components only render the selection state; updating it when the
    user clicks on an item is up to your own server-side or JavaScript code.

Alert
-----

Highlights important messages that require the user's attention. EasyAdmin
uses it, for example, to render the
:ref:`flash messages <customizing-flash-messages>` of your application:

.. code-block:: twig

    <twig:ea:Alert>Your changes have been saved.</twig:ea:Alert>

    <twig:ea:Alert variant="danger">Your password is about to expire.</twig:ea:Alert>

The ``variant`` prop accepts the usual Bootstrap values (``primary``,
``success``, ``warning``, ``danger``, etc.; default: ``info``). Add an optional
``icon``, a ``title`` and a dismiss button as follows:

.. code-block:: twig

    <twig:ea:Alert variant="warning" icon="fa-triangle-exclamation" title="Disk space low" withDismissButton>
        The server is running out of disk space.
    </twig:ea:Alert>

Use the ``title`` block instead of the ``title`` prop when the title needs
custom markup. If the alert requires the user to do something, add buttons or
links in the ``actions`` block:

.. code-block:: twig

    <twig:ea:Alert variant="info" title="New version available">
        A new EasyAdmin version has been released.

        <twig:block name="actions">
            <twig:ea:Button htmlElement="a" href="/changelog" size="sm">Read the changelog</twig:ea:Button>
        </twig:block>
    </twig:ea:Alert>

Badge
-----

Renders a small colored label, commonly used for statuses, counts and tags
(it's also what :doc:`ChoiceField </fields/ChoiceField>` uses to render its
values as badges):

.. code-block:: twig

    <twig:ea:Badge>Draft</twig:ea:Badge>

    <twig:ea:Badge variant="success">Published</twig:ea:Badge>

The ``variant`` prop accepts the usual Bootstrap values (``primary``,
``success``, ``danger``, etc.; default: ``secondary``) plus an ``outline``
value that renders a badge with a border and no background. Add optional
leading and/or trailing icons with the ``icon`` and ``endIcon`` props, and
create pill-shaped badges with ``radius="full"``:

.. code-block:: twig

    <twig:ea:Badge variant="danger" icon="fa-circle-exclamation">Out of stock</twig:ea:Badge>

    <twig:ea:Badge variant="outline" endIcon="fa-arrow-right">See all</twig:ea:Badge>

    <twig:ea:Badge variant="primary" radius="full" size="sm">12</twig:ea:Badge>

Button
------

Renders a button with an optional icon. The ``variant`` prop accepts
``default`` (the default value), ``primary``, ``success``, ``warning``,
``danger`` and ``info``; note that there's no ``secondary`` variant. The
``size`` prop accepts ``sm``, ``md`` (the default value) and ``lg``:

.. code-block:: twig

    <twig:ea:Button>Save draft</twig:ea:Button>

    <twig:ea:Button variant="primary" size="lg" isBlock>Sign in</twig:ea:Button>

    <twig:ea:Button variant="danger" icon="internal:delete">Delete</twig:ea:Button>

Buttons render a ``<button type="submit">`` element by default. Use the
``htmlElement`` prop to render a link that looks like a button, or a
self-contained form that submits to some URL when clicking on the button:

.. code-block:: twig

    {# renders an <a> element #}
    <twig:ea:Button htmlElement="a" href="/products/new" icon="internal:plus">Add product</twig:ea:Button>

    {# renders a <form> that submits a DELETE request to the given URL #}
    <twig:ea:Button htmlElement="form" action="/products/24" method="DELETE" variant="danger">
        Delete product
    </twig:ea:Button>

.. note::

    HTML forms only support the ``GET`` and ``POST`` methods. When passing any
    other ``method`` (``PUT``, ``DELETE``, etc.) the form is submitted as
    ``POST`` with the real method in a ``_method`` hidden field, which is the
    convention supported by Symfony's `http_method_override`_ option.

Other useful props:

* ``withTrailingIcon`` displays the icon after the label instead of before;
* ``isInvisible`` removes the button background and borders (useful for
  icon-only buttons);
* ``inactive`` renders the button in a disabled state;
* ``name`` and ``value`` set those attributes on the ``<button>`` element,
  which is useful to tell apart several submit buttons of the same form.

.. code-block:: twig

    <twig:ea:Button icon="fa-arrow-right" withTrailingIcon>Next step</twig:ea:Button>

    <twig:ea:Button type="button" isInvisible icon="internal:gear" aria-label="Settings"/>

Flag
----

Renders the flag of a country as an inline SVG image (it's the same flag
displayed by :doc:`CountryField </fields/CountryField>`). Pass the two-letter
`ISO 3166-1 alpha-2`_ code of the country and, optionally, display the country
name next to the flag:

.. code-block:: twig

    <twig:ea:Flag countryCode="JP"/>

    <twig:ea:Flag countryCode="JP" showName/>

    {# use countryName to display a custom name instead of the default
       country name provided by the Intl component #}
    <twig:ea:Flag countryCode="JP" showName countryName="Nippon"/>

Flags are ``17px`` tall by default; change that with the ``height`` prop
(width is adjusted automatically):

.. code-block:: twig

    <twig:ea:Flag countryCode="JP" height="{{ 24 }}"/>

Icon
----

Renders the icon associated with the given name, using the icon set configured
in the backend (`FontAwesome icons`_ by default, or
:ref:`your own icon set <icon-customization>`):

.. code-block:: twig

    <twig:ea:Icon name="user"/>

    <twig:ea:Icon name="fa-solid fa-file-invoice"/>

Icons prefixed with ``internal:`` are SVG icons bundled with EasyAdmin and
render the same regardless of the configured icon set. They are useful when
overriding backend templates, to match the look of the default interface:

.. code-block:: twig

    <twig:ea:Icon name="internal:search"/>

Modal
-----

Renders a modal window. Give it an ``id`` and open it from anywhere with a
``Modal:Trigger`` button whose ``target`` prop is a CSS selector pointing to
that modal (e.g. ``target="#delete-modal"``). Inside the modal, use the
``Modal:Close`` button to dismiss it:

.. code-block:: twig

    <twig:ea:Modal:Trigger target="#delete-modal" variant="danger">Delete</twig:ea:Modal:Trigger>

    <twig:ea:Modal id="delete-modal" title="Delete this item?" description="This action cannot be undone.">
        <twig:block name="footer">
            <twig:ea:Modal:Close>Cancel</twig:ea:Modal:Close>
            <twig:ea:Button variant="danger" data-bs-dismiss="modal">Delete</twig:ea:Button>
        </twig:block>
    </twig:ea:Modal>

``Modal:Trigger`` and ``Modal:Close`` wrap the ``ea:Button`` component, so
they accept all its props (``variant``, ``icon``, ``size``, etc.).

Instead of (or in addition to) the ``title`` and ``description`` props, you
can pass any custom markup in the ``body`` block, and add an optional header
above it with the ``header`` block. Use the ``size`` prop to change the default
``380px`` window width: ``sm`` is ``320px``, ``lg`` is ``512px`` and ``xl`` is
``800px``. The ``headerClass``, ``bodyClass`` and ``footerClass`` props add
custom CSS classes to each of those elements:

.. code-block:: twig

    <twig:ea:Modal id="details-modal" size="lg">
        <twig:block name="body">
            <p>Any markup you need goes here.</p>
        </twig:block>
        <twig:block name="footer">
            <twig:ea:Modal:Close>Close</twig:ea:Modal:Close>
        </twig:block>
    </twig:ea:Modal>

For confirmation-style dialogs, display an icon at the top left of the window
with the ``icon`` prop, or stacked on top of the contents with ``topIcon``:

.. code-block:: twig

    <twig:ea:Modal id="warn-modal" topIcon="internal:circle-exclamation" title="Are you sure?">
        <twig:block name="footer">
            <twig:ea:Modal:Close>No</twig:ea:Modal:Close>
            <twig:ea:Button variant="primary" data-bs-dismiss="modal">Yes</twig:ea:Button>
        </twig:block>
    </twig:ea:Modal>

Pagination
----------

Renders a pagination control with links to browse the pages of some results,
and an optional counter of the total number of results. Pass the current page
in the ``currentPage`` prop. Pass the URL pattern used to generate the page
links in ``urlPattern``, which must contain the ``{page}`` placeholder.
Finally, pass either the last page number in ``lastPage`` or the total number
of items and the page size in ``totalItems`` and ``pageSize``:

.. code-block:: twig

    <twig:ea:Pagination currentPage="{{ page }}" lastPage="{{ 20 }}" urlPattern="/products?page={page}"/>

    {# the last page is calculated automatically: ceil(1234 / 25) = 50 pages #}
    <twig:ea:Pagination currentPage="{{ page }}" totalItems="{{ 1234 }}" pageSize="{{ 25 }}" urlPattern="/products?page={page}"/>

Use ``size="sm"`` to render a smaller control and the ``radius`` prop
(``none``, ``sm``, ``md``, ``lg`` or ``full``) to change the border radius of
the page links; when ``radius`` is not set, they inherit the border radius of
your project. ``rangeSize`` sets how many page links are displayed around the
current page (default: ``3``) and ``rangeEdgeSize`` how many are displayed at
the first and last pages (default: ``1``). Labels are translated with the
``translationDomain`` prop (default: ``EasyAdminBundle``).

Several props toggle the elements displayed by the component:

.. code-block:: twig

    <twig:ea:Pagination currentPage="{{ page }}" lastPage="{{ 20 }}" urlPattern="/products?page={page}"
        showFirstLast
        showResultsCount="{{ false }}"
        showPageNumbers="{{ false }}"
        showPreviousNextLabels="{{ false }}"
    />

.. note::

    In templates rendered by EasyAdmin (e.g. when
    :ref:`overriding the index page <index-page-listing>`) you can pass the
    ``paginator`` object instead of all the above props:
    ``<twig:ea:Pagination paginator="{{ paginator }}"/>``. Both ways of configuring
    the component are mutually exclusive.

Sidebar
-------

Renders the navigation sidebar of the backend. It's a small set of composable
subcomponents inspired by the `shadcn/ui Sidebar`_ component, but simplified:
a header, a scrollable content area, groups of items and optional submenus.
EasyAdmin renders the :ref:`main menu <dashboard-menu>` with these components
automatically, so use them only when building a fully custom sidebar:

.. code-block:: twig

    <twig:ea:Sidebar>
        <twig:ea:Sidebar:Header>
            <a class="logo" href="/admin">ACME Corp.</a>
        </twig:ea:Sidebar:Header>

        <twig:ea:Sidebar:Content>
            {# pass 'label' to add a heading; a group without it renders only its items #}
            <twig:ea:Sidebar:Group label="Content">
                <twig:ea:Sidebar:Item label="Dashboard" href="/admin" icon="fa-home" active/>
                <twig:ea:Sidebar:Item label="Blog Posts" href="/admin/posts" icon="fa-newspaper"/>
            </twig:ea:Sidebar:Group>
        </twig:ea:Sidebar:Content>
    </twig:ea:Sidebar>

Groups also accept an ``icon`` prop, displayed before the group label. Items
accept the ``target`` and ``rel`` props to set those attributes on the
generated link, plus ``cssClass`` and ``htmlAttributes`` to add CSS classes and
arbitrary HTML attributes to the ``<a>``/``<button>`` element.

Items with a submenu are not clickable: they only expand/collapse their
submenu (rendered inline in the normal sidebar and as a flyout panel in the
compact icon-only sidebar). Add the ``keepOpen`` prop to render the submenu
always expanded and not collapsible. Pass the submenu contents via the
``submenu`` slot:

.. code-block:: twig

    <twig:ea:Sidebar:Item label="Settings" icon="fa-gear" hasSubmenu expanded>
        <twig:block name="submenu">
            <twig:ea:Sidebar:Submenu label="Settings">
                <twig:ea:Sidebar:Item label="General" href="/admin/settings"/>
                <twig:ea:Sidebar:Item label="Security" href="/admin/settings/security"/>
            </twig:ea:Sidebar:Submenu>
        </twig:block>
    </twig:ea:Sidebar:Item>

Items can display a badge (e.g. a counter) using the ``badge`` slot:

.. code-block:: twig

    <twig:ea:Sidebar:Item label="Orders" href="/admin/orders" icon="fa-cart-shopping">
        <twig:block name="badge">
            <twig:ea:Badge radius="full">7</twig:ea:Badge>
        </twig:block>
    </twig:ea:Sidebar:Item>

The optional footer is always anchored to the bottom of the screen: when the
menu is taller than the viewport, the menu items scroll underneath it. Use it
for important messages, notices, etc. (unlike the ``main_menu_after``
:ref:`template block <template-customization>`, which renders in the normal
flow right after the menu items). The footer grows with its contents, so keep
them short or make them scrollable yourself:

.. code-block:: twig

    <twig:ea:Sidebar:Footer>
        <twig:ea:Alert variant="warning">Scheduled maintenance at 22:00 UTC</twig:ea:Alert>
    </twig:ea:Sidebar:Footer>

In the default sidebar, add footer contents by overriding the ``sidebar_footer``
block in your dashboard templates.

The sidebar supports two widths: the default one and a
:ref:`compact mode <dashboard-configuration>` that only displays the item
icons. Users switch between them by clicking on the sidebar edge, and the
selected mode is persisted in the browser local storage. In compact mode,
hovering an item shows a tooltip with its label, hovering an item with a
submenu shows the submenu as a flyout panel, and the footer is hidden
(arbitrary contents can't fit in the icon rail).

Switch
------

Renders an accessible toggle switch, used to represent boolean values
(internally it's an ``<input type="checkbox">`` field, so you can use it in
your own forms). It's also what :doc:`BooleanField </fields/BooleanField>`
renders when displaying boolean values as switches:

.. code-block:: twig

    <twig:ea:Switch/>

    <twig:ea:Switch checked/>

    {# when submitting the form, this sends settings[newsletter]=yes #}
    <twig:ea:Switch name="settings[newsletter]" value="yes" checked/>

Use the ``id`` prop to wire an external ``<label for="...">`` element to the
switch, and the ``required`` prop to make it mandatory in your form.

By default, the checked state uses the primary color of the backend. Use the
``variant`` prop (``success``, ``warning`` or ``danger``) to change it, and
``size="sm"`` to render a smaller switch:

.. code-block:: twig

    <twig:ea:Switch variant="success" checked/>

    <twig:ea:Switch size="sm" disabled/>

.. tip::

    When the switch doesn't have a visible ``<label>`` element associated with
    it, pass the ``ariaLabel`` prop to keep it accessible to screen readers.

Tabs
----

Renders a bar of clickable tabs, like the one displayed on the ``new``,
``edit`` and ``detail`` pages of entities that define form tabs. The tab
contents are not part of the component: each item links to the ``id`` of one
of your own ``.tab-pane`` elements, which are shown and hidden as the user
selects the tabs:

.. code-block:: twig

    <twig:ea:Tabs>
        <twig:ea:Tabs:Item paneId="tab-general" label="General" active/>
        <twig:ea:Tabs:Item paneId="tab-contact" label="Contact" icon="fa-envelope"/>
    </twig:ea:Tabs>

    <div class="tab-content">
        <div class="tab-pane active" id="tab-general">...</div>
        <div class="tab-pane" id="tab-contact">...</div>
    </div>

Item labels are rendered as HTML and are not translated, so pass them already
translated. Use the ``errorCount`` prop to display a badge with the number of
form validation errors of the fields in that tab:

.. code-block:: twig

    <twig:ea:Tabs:Item paneId="tab-billing" label="{{ 'Billing'|trans }}" errorCount="{{ 3 }}"/>

.. note::

    In templates rendered by EasyAdmin (e.g. when overriding the form or the
    detail pages) you can pass the collection of :ref:`form tabs <form-tabs>`
    created with ``FormField::addTab()`` in the ``tabs`` prop, and the component
    renders one item per tab. Their labels are translated with the domain given
    in the ``translationDomain`` prop (default: ``messages``). This is what the
    ``crud/detail/tab_list`` template does:

    .. code-block:: twig

        <twig:ea:Tabs tabs="{{ field.getCustomOption('tabs') }}" translationDomain="{{ ea().i18n.translationDomain }}"/>

.. _`Twig Components`: https://symfony.com/bundles/ux-twig-component/current/index.html
.. _`shadcn/ui Sidebar`: https://ui.shadcn.com/docs/components/base/sidebar
.. _`FontAwesome icons`: https://fontawesome.com/v6/search?m=free
.. _`ISO 3166-1 alpha-2`: https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2
.. _`http_method_override`: https://symfony.com/doc/current/reference/configuration/framework.html#http-method-override
