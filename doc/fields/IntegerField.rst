EasyAdmin Integer Field
=======================

This field is used to represent the value of properties that store integer numbers.

In :ref:`form pages (edit and new) <crud-pages>` it looks like this:

.. image:: ../images/fields/field-integer.png
   :alt: Default style of EasyAdmin integer field

Basic Information
-----------------

* **PHP Class**: ``EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField``
* **Doctrine DBAL Type** used to store this value: ``integer`` or ``smallint``
* **Symfony Form Type** used to render the field: `IntegerType`_
* **Rendered as**:

  .. code-block:: html

    <input type="number">

Options
-------

setNumberFormat
~~~~~~~~~~~~~~~

By default, the integer value is displayed "as is". If you prefer to format the
value in any way, use this option and pass any formatting string valid as an
argument of the ``sprintf()`` function::

    // this would display integers in scientific notation (e.g. 1234567890 = '1.234568e+9')
    yield IntegerField::new('...')->setNumberFormat('%e');

    // formatting also helps you to add left/right padding to numbers
    // the following example would format 123 as '+00123'
    yield IntegerField::new('...')->setNumberFormat('%+06d');

setThousandsSeparator
~~~~~~~~~~~~~~~~~~~~~

By default, the integer value doesn't separate each thousands group in any way
(e.g. ``12345`` is displayed like that, instead of ``12,345``). Use this option
to set the character to use to separate each thousands group::

    // this would display '12345' as '12 345'
    yield IntegerField::new('...')->setThousandsSeparator(' ');

.. _`IntegerType`: https://symfony.com/doc/current/reference/forms/types/integer.html
