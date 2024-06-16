EasyAdmin Association Field
===========================

This field displays the contents of a property used to associate Doctrine entities
between them (of any type: one-to-one, one-to-many, etc.) In form pages this
field is rendered using an advanced autocomplete widget based on `TomSelect`_ library.

In :ref:`form pages (edit and new) <crud-pages>` it looks like this:

.. image:: ../images/fields/field-association.png
   :alt: Default style of EasyAdmin association field

In read-only pages (``index``and ``detail``) is displayed as a clickable link
pointing to the ``detail`` action of the related entity.

Basic Information
-----------------

* **PHP Class**: ``EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField``
* **Doctrine DBAL Type** used to store this value: ``integer``, ``guid`` or any
  other type that you use to store the ID of the associated entity
* **Symfony Form Type** used to render the field: `EntityType`_
* **Rendered as**:

  .. code-block:: html

    <!-- when loading the page this is transformed into a dynamic field via JavaScript -->
    <select> ... </select>

Options
-------

``autocomplete``
~~~~~~~~~~~~~~~~

By default, the field loads all the possible values of the related entity. This
creates "out of memory" errors when that entity has hundreds or thousands of values.
Use this option to load values dynamically (via Ajax requests) based on user input::

    yield AssociationField::new('...')->autocomplete();

``renderAsNativeWidget``
~~~~~~~~~~~~~~~~~~~~~~~~

By default, this field is rendered using an advanced JavaScript widget created
with the `TomSelect`_ library. If you prefer to display a standard ``<select>``
element, use this option::

    yield AssociationField::new('...')->renderAsNativeWidget();

``renderAsEmbeddedForm``
~~~~~~~~~~~~~~~~~~~~~~~~

By default, to-one associations are rendered in forms as dropdowns where you can
select one of the given values. For example, a blog post associated with one
author will show a dropdown list to select one of the available authors.

However, sometimes the associated property refers to a `value object`_. For example,
a ``Customer`` entity related to an ``Address`` entity or a ``Server`` entity
related to an ``IpAddres`` entity.

In these cases it doesn't make sense to display a dropdown with all the
(potentially millions!) addresses. Instead, it's better to embed the form fields
of the related entity (e.g. ``Address``) inside the form of the entity that you
are creating or editing (e.g. ``Customer``).

The ``renderAsEmbeddedForm()`` option tells EasyAdmin to embed the CRUD form of
the associated property instead of showing all its possible values in a dropdown::

    yield AssociationField::new('...')->renderAsEmbeddedForm();

EasyAdmin looks for the :doc:`CRUD controller </crud>` associated to the property
automatically. If you need better control about which CRUD controller to use,
pass the fully-qualified class name of the controller as the first argument::

    yield AssociationField::new('...')->renderAsEmbeddedForm(CategoryCrudController::class);

    // the other optional arguments are the page names passed to the configureFields()
    // method of the CRUD controller (this allows you to have a better control of
    // the fields displayed on different scenarios)
    yield AssociationField::new('...')->renderAsEmbeddedForm(
        CategoryCrudController::class, 'create_category_inside_an_article', 'edit_category_inside_an_article'
    );

``setCrudController``
~~~~~~~~~~~~~~~~~~~~~

In read-only pages (``index`` and ``detail``) this field is displayed as a
clickable link that points to the ``detail`` page of the related entity.

By default, EasyAdmin finds the CRUD controller of the related entity automatically.
However, if you define more than one CRUD controller for that entity, you'll need
to use this option to specify which one to use for the links::

    yield AssociationField::new('...')->setCrudController(SomeCrudController::class);

``setQueryBuilder``
~~~~~~~~~~~~~~~~~~~

By default, EasyAdmin uses a generic database query to find the items of the
related entity. Use this option if you need to use a custom query to filter results
or to sort them in some specific way.

Similar to the `query_builder option`_ of Symfony's ``EntityType``, the value of
this option can be a ``Doctrine\ORM\QueryBuilder`` object or a ``callable``.

You can use the ``QueryBuilder`` objects when the custom query is short and not
reused everywhere else in the application::

    // get the entity repository somehow...
    $someRepository = $this->entityManager->getRepository(SomeEntity::class);

    yield AssociationField::new('...')->setQueryBuilder(
        $someRepository->createQueryBuilder('entity')
            ->where('entity.some_property = :some_value')
            ->setParameter('some_value', '...')
            ->orderBy('entity.some_property', 'ASC')
    );

Using callables is more convenient when custom queries are complex and are
already defined in the entity repository because they are reused in other parts
of the application. When using a callable, the ``QueryBuilder`` is
automatically injected by Symfony as the first argument::

    yield AssociationField::new('...')->setQueryBuilder(
        fn (QueryBuilder $queryBuilder) => $queryBuilder->addCriteria('...')
    );

Or if you prefer using the repository of the entity::

    yield AssociationField::new('...')->setQueryBuilder(
        fn (QueryBuilder $queryBuilder) => $queryBuilder->getEntityManager()->getRepository(Foo::class)->findBySomeCriteria();
    );

setSortProperty
~~~~~~~~~~~~~~~

If you sort the ``index`` page results using an association field, by default
those results are sorted using the ``id`` property of the associated entity.
Set this option to sort results using any of the other properties of the
associated entity::

    yield AssociationField::new('user')->setSortProperty('name');

.. _`TomSelect`: https://tom-select.js.org/
.. _`EntityType`: https://symfony.com/doc/current/reference/forms/types/entity.html
.. _`query_builder option`: https://symfony.com/doc/current/reference/forms/types/entity.html#query-builder
.. _`value object`: https://en.wikipedia.org/wiki/Value_object
