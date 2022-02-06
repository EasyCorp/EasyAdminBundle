EasyAdmin URL Field
===================

This field is used to represent a text content that stores a single URL.

In :ref:`form pages (edit and new) <crud-pages>` it looks like this:

.. image:: ../images/fields/field-url.png
   :alt: Default style of EasyAdmin URL field

Basic Information
-----------------

* **PHP Class**: ``EasyCorp\Bundle\EasyAdminBundle\Field\UrlField``
* **Doctrine DBAL Type** used to store this value: ``string``
* **Symfony Form Type** used to render the field: `UrlType`_
* **Rendered as**:

  .. code-block:: html

    <input type="url" value="...">

Options
-------

This field does not define any custom option.

.. _`UrlType`: https://symfony.com/doc/current/reference/forms/types/url.html
