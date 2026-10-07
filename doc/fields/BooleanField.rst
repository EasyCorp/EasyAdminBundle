EasyAdmin Boolean Field
=======================

This field displays the ``true``/``false`` value of a boolean property.

In :ref:`form pages (edit and new) <crud-pages>` it looks like this (it's like
an advanced ``<input type="checkbox">`` created with JavaScript):

.. image:: ../images/fields/field-boolean.png
   :alt: Default style of EasyAdmin boolean field

In read-only pages (``index`` and ``detail``) it renders either as a static
``Yes``/``No`` label or as a dynamic switch/toggle that can flip the value when
clicking on it.

Basic Information
-----------------

* **PHP Class**: ``EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField``
* **Doctrine DBAL Type** used to store this value: ``boolean``
* **Symfony Form Type** used to render the field: `CheckboxType`_
* **Rendered as**:

  .. code-block:: html

    <input type="checkbox">

Options
-------

``hideValueWhenFalse``
~~~~~~~~~~~~~~~~~~~~~~

Use this option to not display anything when the field value is ``false``. This
is useful to simplify the backend display in listings where most rows have the same
``false`` value and you want to ignore those and better highlight the rows with
the ``true`` value::

    yield BooleanField::new('...')->hideValueWhenFalse();

Keep in mind that:

* This option is ignored when using the ``renderAsSwitch()`` option, which always
  displays a switch/toggle with the field value;
* This option is only applied to the ``index`` page; in the ``detail`` page you
  will always see the field value to avoid any confusion.

``hideValueWhenTrue``
~~~~~~~~~~~~~~~~~~~~~

Use this option to not display anything when the field value is ``true``. This
is useful to simplify the backend display in listings where most rows have the same
``true`` value and you want to ignore those and better highlight the rows with
the ``false`` value::

    yield BooleanField::new('...')->hideValueWhenTrue();

Keep in mind that:

* This option is ignored when using the ``renderAsSwitch()`` option, which always
  displays a switch/toggle with the field value;
* This option is only applied to the ``index`` page; in the ``detail`` page you
  will always see the field value to avoid any confusion.

``renderAsSwitch``
~~~~~~~~~~~~~~~~~~

By default, in read-only pages (``index`` and ``detail``) this field is rendered
as a dynamic switch/toggle that can flip the underlying value when clicking on it.
This switch is rendered with the :doc:`Switch component </components>`. If you
prefer to not allow changing the property value in this way, use this option::

    yield BooleanField::new('...')->renderAsSwitch(false);

``renderAsSuccessSwitch``
~~~~~~~~~~~~~~~~~~~~~~~~~

By default the switch uses a neutral color when it's turned on. Use this option
to color the "on" state with the same color as the ``success`` (green) buttons::

    yield BooleanField::new('...')->renderAsSuccessSwitch();

This method also enables the switch rendering, so you don't need to call
``renderAsSwitch()`` too. The color is applied everywhere the field is rendered
as a switch (the ``index`` toggle and the ``edit``/``new`` forms). The "off"
state always uses the neutral color. The same applies to the two options below.

``renderAsWarningSwitch``
~~~~~~~~~~~~~~~~~~~~~~~~~

Same as ``renderAsSuccessSwitch()``, but the "on" state uses the same color as
the ``warning`` (amber) buttons::

    yield BooleanField::new('...')->renderAsWarningSwitch();

``renderAsDangerSwitch``
~~~~~~~~~~~~~~~~~~~~~~~~

Same as ``renderAsSuccessSwitch()``, but the "on" state uses the same color as
the ``danger`` (red) buttons::

    yield BooleanField::new('...')->renderAsDangerSwitch();

``swapLabelAndValue``
~~~~~~~~~~~~~~~~~~~~~

In the ``detail`` page, boolean fields swap the usual label <-> value order to
display the value (which is always a tiny ``Yes``/``No`` badge or a switch)
before the label. If you prefer to display the label and the value of boolean
fields in the same order as the rest of the fields, use this option::

    yield BooleanField::new('...')->swapLabelAndValue(false);

.. _`CheckboxType`: https://symfony.com/doc/current/reference/forms/types/checkbox.html
