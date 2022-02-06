EasyAdmin Hidden Field
======================

This is a very special field used to include hidden fields in the forms used
to create and edit entities. Most probably you'll never use this field, but it
could help in edge-cases.

Basic Information
-----------------

* **PHP Class**: ``EasyCorp\Bundle\EasyAdminBundle\Field\HiddenField``
* **Doctrine DBAL Type** used to store this value: anything (it depends on the
  value of the property used to include it as a hidden field)
* **Symfony Form Type** used to render the field: `HiddenType`_
* **Rendered as**:

  .. code-block:: html

    <input type="hidden" value="...">

Options
-------

This field does not define any custom option.

.. _`HiddenType`: https://symfony.com/doc/current/reference/forms/types/hidden.html
