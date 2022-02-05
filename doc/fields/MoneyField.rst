EasyAdmin Money Field
=====================

This field is used to represent the value of properties that store amounts of
money.

In :ref:`form pages (edit and new) <crud-pages>` it looks like this:

.. image:: ../images/fields/field-money.png
   :alt: Default style of EasyAdmin money field

Basic Information
-----------------

* **PHP Class**: ``EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField``
* **Doctrine DBAL Type** used to store this value: ``decimal``, ``float`` or
  ``integer``
* **Symfony Form Type** used to render the field: `MoneyType`_
* **Rendered as**:

  .. code-block:: html

    <input type="number">

Options
-------

setCurrency
~~~~~~~~~~~

The currency associated to the amount of money is needed to format the field
value in read-only pages (``index`` and ``detail``). If the currency is known and
the same for all values of the field, use this option (otherwise, use the
``setCurrencyPropertyPath`` option).

The method argument must be a valid `ISO 4217 standard`_ currency code::

    // e.g. 'INR' = 'Indian Rupee'
    yield MoneyField('...')->setCurrency('INR');

setCurrencyPropertyPath
~~~~~~~~~~~~~~~~~~~~~~~

The currency associated to the amount of money is needed to format the field
value in read-only pages (``index`` and ``detail``). If the currency changes
for each field value, you'll probably store that currency code (following the
`ISO 4217 standard`_) as a property of the entity.

Use this option to tell EasyAdmin which is the property that stores the currency
code. The method argument is any valid `Symfony PropertyAccess`_ expression::

    yield MoneyField('...')->setCurrencyPropertyPath('currency');
    yield MoneyField('...')->setCurrencyPropertyPath('currencySymbol');
    yield MoneyField('...')->setCurrencyPropertyPath('currency.code');

setNumDecimals
~~~~~~~~~~~~~~

By default, money amounts are displayed formatted with 2 decimal numbers. Use
this option if you want to format values with a different number of decimals::

    yield MoneyField::new('...')->setNumDecimals(0);

setStoredAsCents
~~~~~~~~~~~~~~~~

Although it may seem over-complicated at first, the most recommended way to
store money amounts in the database is to use cents. For example, "5 euros"
would be stored as ``500``(5 x 100 cents) and "349.99 yens" would be stored as
``34,999``. Doing this solves all the rounding problems that you'll find when
storing money amounts using float or decimal numbers.

.. tip::

    In Symfony/PHP applications you can use the `Money PHP`_ library to handle
    the conversion of money amounts from/into cents.

If you follow this practice, use this option to tell EasyAdmin to convert from/into
cents automatically when displaying and storing money amounts::

    yield MoneyField::new('...')->setStoredAsCents();

.. _`MoneyType`: https://symfony.com/doc/current/reference/forms/types/money.html
.. _`ISO 4217 standard`: https://en.wikipedia.org/wiki/ISO_4217
.. _`Symfony PropertyAccess`: https://symfony.com/doc/current/components/property_access.html
.. _`Money PHP`: https://github.com/moneyphp/money
