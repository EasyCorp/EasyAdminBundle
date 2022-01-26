EasyAdmin Telephone Field
=========================

This field is used to represent a text content that stores a single telephone number.

In :ref:`form pages (edit and new) <crud-pages>` it looks like this:

.. image:: ../images/fields/field-telephone.png
   :alt: Default style of EasyAdmin telephone field

Basic Information
-----------------

* **PHP Class**: ``EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField``
* **Doctrine DBAL Type** used to store this value: `string`
* **Symfony Form Type** used to render the field: `TelType`_
* **Rendered as**:

  .. code-block:: html

    <input type="tel" value="...">

Options
-------

This field does not define any custom option.

.. _`TelType`: https://symfony.com/doc/current/reference/forms/types/tel.html
