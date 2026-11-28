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

``setNumberFormat``
~~~~~~~~~~~~~~~~~~~

By default, integers are formatted according to the current locale. If you
prefer to format the value in any way, use this option and pass any formatting
string valid as an argument of the ``sprintf()`` function::

    // this would display integers in scientific notation (e.g. 1234567890 = '1.234568e+9')
    yield IntegerField::new('...')->setNumberFormat('%e');

    // formatting also helps you to add left/right padding to integers
    // the following example would format 123 as '+00123'
    yield IntegerField::new('...')->setNumberFormat('%+06d');

This option overrides, for this field only, the value of the
:ref:`setNumberFormat() CRUD option <crud-date-time-number-format-options>`.

.. caution::

    Using this option makes EasyAdmin ignore all the other number formatting
    options.

``setThousandsSeparator``
~~~~~~~~~~~~~~~~~~~~~~~~~

By default, the numeric value uses the thousands separator of the current
locale (e.g. ``12345`` is displayed as ``12,345`` in the English locale).
Use this option to set a different character to separate thousands groups::

    // this would display '12345' as '12 345'
    yield IntegerField::new('...')->setThousandsSeparator(' ');

This option overrides, for this field only, the value of the
:ref:`setThousandsSeparator() CRUD option <crud-date-time-number-format-options>`.

.. _`IntegerType`: https://symfony.com/doc/current/reference/forms/types/integer.html
