EasyAdmin Date Interval Field
=============================

This field is used to represent a value that stores a PHP ``DateInterval``
object (e.g. a duration mapped to Doctrine's ``dateinterval`` column type).

In :ref:`form pages (edit and new) <crud-pages>` it is rendered as a single
text input that expects an `ISO 8601 duration`_ pattern (for example ``P1Y2M3D``
for "1 year, 2 months and 3 days" or ``PT1H30M`` for "1 hour and 30 minutes").

In :ref:`read-only pages (index and detail) <crud-pages>` the value is rendered
as a localized, human-friendly string (for example ``2 years 4 days 6 hours
8 minutes``). Each part is translated and pluralized using the
``EasyAdminBundle`` translation domain.

Basic Information
-----------------

* **PHP Class**: ``EasyCorp\Bundle\EasyAdminBundle\Field\DateIntervalField``
* **Doctrine DBAL Type** used to store this value: ``dateinterval``
* **Symfony Form Type** used to render the field: `DateIntervalType`_
* **Rendered as**:

  .. code-block:: html

    <input type="text" placeholder="P1Y2M3DT4H5M6S">

Options
-------

setFormat
~~~~~~~~~

By default, in read-only pages (``index`` and ``detail``) date intervals are
displayed using a localized, pluralized representation built from the
``date_interval.*`` translation keys.

Use this option to override that default with a raw format string passed to
``DateInterval::format()``::

    yield DateIntervalField::new('duration')->setFormat('%y years, %m months, %d days');

The same override is available globally on the CRUD configuration via
:ref:`Crud::setDateIntervalFormat() <crud-date-time-number-format-options>`.

.. _`ISO 8601 duration`: https://en.wikipedia.org/wiki/ISO_8601#Durations
.. _`DateIntervalType`: https://symfony.com/doc/current/reference/forms/types/dateinterval.html
