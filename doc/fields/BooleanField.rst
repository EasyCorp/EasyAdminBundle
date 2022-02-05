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

``renderAsSwitch``
~~~~~~~~~~~~~~~~~~

By default, in read-only pages (``index`` and ``detail``) this field is rendered
as a dynamic switch/toggle that can flip the underlying value when clicking on it.
If you prefer to not allow changing the property value in this way, use this option::

    yield BooleanField::new('...')->renderAsSwitch(false);

.. _`CheckboxType`: https://symfony.com/doc/current/reference/forms/types/checkbox.html
