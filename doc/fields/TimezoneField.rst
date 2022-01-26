EasyAdmin Timezone Field
========================

This field is used to represent the name of a timezone stored in a property as
a valid `PHP timezone ID`_.

In :ref:`form pages (edit and new) <crud-pages>` it looks like this:

.. image:: ../images/fields/field-timezone.png
   :alt: Default style of EasyAdmin timezone field

Basic Information
-----------------

* **PHP Class**: ``EasyCorp\Bundle\EasyAdminBundle\Field\TimezoneField``
* **Doctrine DBAL Type** used to store this value: ``string``
* **Symfony Form Type** used to render the field: `TimezoneType`_
* **Rendered as**:

  .. code-block:: html

    <select> ... </select>

Options
-------

This field does not define any custom option.

.. _`TimezoneType`: https://symfony.com/doc/current/reference/forms/types/timezone.html
.. _`PHP timezone ID`: https://www.php.net/manual/en/timezones.php
