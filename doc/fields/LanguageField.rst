EasyAdmin Language Field
========================

This field is used to represent the name of a language stored in a property as
a valid `ICU project`_ language code (the same which is used by Symfony and many
other tech projects).

In :ref:`form pages (edit and new) <crud-pages>` it looks like this:

.. image:: ../images/fields/field-language.png
   :alt: Default style of EasyAdmin language field

Basic Information
-----------------

* **PHP Class**: ``EasyCorp\Bundle\EasyAdminBundle\Field\LanguageField``
* **Doctrine DBAL Type** used to store this value: ``string``
* **Symfony Form Type** used to render the field: `LanguageType`_
* **Rendered as**:

  .. code-block:: html

    <select> ... </select>

Options
-------

``includeOnly``
~~~~~~~~~~~~~~~

By default, the locale selector displays all the languages defined by
the `ICU project`_, the same which is used by Symfony and many other tech projects.
Use this option to only display the given language codes::

    yield LanguageField::new('...')->includeOnly(['en', 'fr', 'pl']);

``remove``
~~~~~~~~~~

By default, the locale selector displays all the languages defined by
the `ICU project`_, the same which is used by Symfony and many other tech projects.
Use this option to remove the given language codes from that list::

    yield LanguageField::new('...')->remove(['fr', 'pl']);

``showCode``
~~~~~~~~~~~~

By default, in read-only pages (``index`` and ``show``) this field displays the
full name of the language (e.g. ``Arabic``, ``Burmese``, ``Slovenian``, etc.)

Use this option if you want to display the language code (e.g. ``ar``, ``my``,
``sl``, etc.) instead of or in addition to the language name::

    yield LanguageField::new('...')->showCode();

``showName``
~~~~~~~~~~~~

By default, in read-only pages (``index`` and ``show``) this field displays the
full name of the language (e.g. ``Arabic``, ``Burmese``, ``Slovenian``, etc.)

Use this option if you want to hide this name and display instead the language
code (e.g. ``ar``, ``my``, ``sl``, etc.)::

    yield LanguageField::new('...')->showName(false);

``useAlpha3Codes``
~~~~~~~~~~~~~~~~~~

By default, the field expects that the given language code is a 2-letter value
following the `ISO 639-1 alpha-2`_ format. Use this option if you store the
language code using the 3-letter value of the `ISO 639-2 alpha-3`_ format::

    yield LanguageField::new('...')->useAlpha3Codes();

.. _`LanguageType`: https://symfony.com/doc/current/reference/forms/types/language.html
.. _`ICU project`: https://icu.unicode.org/
.. _`ISO 639-1 alpha-2`: https://en.wikipedia.org/wiki/ISO_639-1
.. _`ISO 639-2 alpha-3`: https://en.wikipedia.org/wiki/ISO_639-2
