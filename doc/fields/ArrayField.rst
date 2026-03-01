EasyAdmin Array Field
=====================

This field displays the contents of a property which is mapped to a `Doctrine Array type`_ and it
allows you to add new elements dynamically using JavaScript.

In :ref:`form pages (edit and new) <crud-pages>` it looks like this:

.. image:: ../images/fields/field-array.png
   :alt: Default style of EasyAdmin array field

Basic Information
-----------------

* **PHP Class**: ``EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField``
* **Doctrine DBAL Type** used to store this value: it depends on the installed DBAL version
  * DBAL **v4**: ``simple_array`` or ``json``
  * DBAL **v3**: ``array``, ``simple_array`` or ``json``
  * DNAL **v2**: ``array``, ``simple_array``, ``json_array`` or ``json``
* **Symfony Form Type** used to render the field: `CollectionType`_
* **Rendered as**:

  .. code-block:: html

    <!-- when loading the page this is transformed into a dynamic collection via JavaScript -->
    <input type="text" value="...">
    <input type="text" value="...">
    <!-- ... -->

Options
-------

This field does not define any custom option.

.. _`Doctrine Array type`: https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/types.html#array-types
.. _`CollectionType`: https://symfony.com/doc/current/reference/forms/types/collection.html
