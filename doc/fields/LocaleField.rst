EasyAdmin LocaleField
=====================

This field is used to represent the name of a locale stored in a property as
a valid `ICU project`_ locale code (the same which is used by Symfony and many
other tech projects).

In :ref:`form pages (edit and new) <crud-pages>` it looks like this:

.. image:: ../images/fields/field-locale.png
   :alt: Default style of EasyAdmin locale field

Basic Information
-----------------

* **PHP Class**: ``EasyCorp\Bundle\EasyAdminBundle\Field\LocaleField``
* **Doctrine DBAL Type** used to store this value: ``string``
* **Symfony Form Type** used to render the field: `LocaleType`_
* **Rendered as**:

  .. code-block:: html

    <select> ... </select>

Options
-------

``showCode``
~~~~~~~~~~~~

By default, in read-only pages (``index`` and ``show``) this field displays the
full name of the locale (e.g. ``Somali (Djibouti)``, ``Uyghur (China)``,
``Ukrainian``, etc.)

Use this option if you want to display the locale code (e.g. ``so_DJ``,
``ug_CN``, ``uk``, etc.) instead of or in addition to the locale name::

    yield LocaleField::new('...')->showCode();

``showName``
~~~~~~~~~~~~

By default, in read-only pages (``index`` and ``show``) this field displays the
full name of the locale (e.g. ``Somali (Djibouti)``, ``Uyghur (China)``,
``Ukrainian``, etc.)

Use this option if you want to hide this name and display instead the locale
code (e.g. ``so_DJ``, ``ug_CN``, ``uk``, etc.)::

    yield LocaleField::new('...')->showName(false);

.. _`LocaleType`: https://symfony.com/doc/current/reference/forms/types/locale.html
.. _`ICU project`: https://icu.unicode.org/
