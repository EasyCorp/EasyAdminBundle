EasyAdmin Email Field
=====================

This field is used to represent a text content that stores a single email address.

In :ref:`form pages (edit and new) <crud-pages>` it looks like this:

.. image:: ../images/fields/field-email.png
   :alt: Default style of EasyAdmin email field

Basic Information
-----------------

* **PHP Class**: ``EasyCorp\Bundle\EasyAdminBundle\Field\EmailField``
* **Doctrine DBAL Type** used to store this value: ``string``
* **Symfony Form Type** used to render the field: `EmailType`_
* **Rendered as**:

  .. code-block:: html

    <input type="email" value="...">

Options
-------

This field does not define any custom option.

.. _`EmailType`: https://symfony.com/doc/current/reference/forms/types/email.html
