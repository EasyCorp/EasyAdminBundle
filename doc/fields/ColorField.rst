EasyAdmin Color Field
=====================

This field is used to represent a text content that stores a single color value
following the `HTML 5 color format`_ (a 7-character string specifying an RGB
color in lower case hexadecimal notation: ``#000000``).

In :ref:`form pages (edit and new) <crud-pages>` it looks like this:

.. image:: ../images/fields/field-color.png
   :alt: Default style of EasyAdmin color field

Basic Information
-----------------

* **PHP Class**: ``EasyCorp\Bundle\EasyAdminBundle\Field\ColorField``
* **Doctrine DBAL Type** used to store this value: ``string``
* **Symfony Form Type** used to render the field: `ColorType`_
* **Rendered as**:

  .. code-block:: html

    <input type="color" value="...">

Options
-------

``showSample``
~~~~~~~~~~~~~~

By default, in read-only pages (``index`` and ``detail``) the field is represented
as a sample of its color (using a ``<span>`` HTML element with a background color
of the same value). If you prefer to not display that sample, use this option::

    yield ColorField::new('...')->showSample(false);

``showValue``
~~~~~~~~~~~~~

By default, in read-only pages (``index`` and ``detail``) the hexadecimal value
of the color is not displayed (only a sample of the color is displayed). Use
this option to display that hexadecimal value::

    yield ColorField::new('...')->showValue();

.. _`HTML 5 color format`: https://www.w3.org/TR/html52/sec-forms.html#color-state-typecolor
.. _`ColorType`: https://symfony.com/doc/current/reference/forms/types/color.html
