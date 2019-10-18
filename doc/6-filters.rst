Filters
=======

The listings of the ``index`` page can be refined with **filters**, a series of
form controls that add conditions to the query (e.g. ``price > 10``, ``enabled = true``).
Define your filters with the ``filters()`` method of ``IndexPageConfig``::

    namespace App\Controller\Admin;

    class UserAdminController
    {
        // ...

        public function getIndexPageConfig(): IndexPageConfig
        {
            return IndexPageConfig::new()
                // ...
                // this is a variadic method that accepts any number of arguments
                ->filters('country', 'status', 'signupDate', 'numPurchases');
        }
    }

EasyAdmin provides ready-to-use filters for the most common needs (dates,
numeric values, collections, etc.). The type of filter is automatically selected
based on the data type of the property, but you can also define the filter type
explicitly::

    namespace App\Controller\Admin;

    use EasyCorp\Bundle\EasyAdminBundle\Filter\IntegerFilter;

    class UserAdminController
    {
        // ...

        public function getIndexPageConfig(): IndexPageConfig
        {
            return IndexPageConfig::new()
                // ...
                // there is no need to define the filter type because EasyAdmin can guess it
                ->filters('country', 'status', 'signupDate', IntegerFilter::new('numPurchases'));
        }
    }

Built-in Filters
----------------

These are the built-in filters provided by EasyAdmin:

* ``ArrayFilter``: applied by default to array fields. It's rendered as a ``<select>`` list
  with the condition (equal/not equal) and another ``<select>`` tags input to introduce
  the comparison value.
* ``BooleanFilter``: applied by default to boolean fields. It's rendered as two
  radio buttons labeled "Yes" and "No".
* ``DateIntervalFilter``: applied by default to date interval fields. It's rendered
  as a ``<select>`` list with the condition (before/after/etc.) and another ``<select>``
  list to choose the comparison value.
* ``DatetimeFilter``, ``date`` or ``time``: applied by default to datetime, date
  or time fields respectively. It's rendered as a ``<select>`` list with the condition
  (before/after/etc.) and a browser native datepicker to pick the date/time.
* ``EntityFilter``: applied to fields with Doctrine associations (all kinds
  supported). It's rendered as a ``<select>`` list with the condition (equal/not
  equal/etc.) and another ``<select>`` list to choose the comparison value.
* ``IntegerFilter``, ``DecimalFilter`` or ``FloatFilter``: applied by default to numeric fields.
  It's rendered as a ``<select>`` list with the condition (higher/lower/equal/etc.) and a
  ``<input>`` to define the comparison value.
* ``TextFilter`` or ``TextareaFilter``: applied by default to string/text fields. It's rendered as a
  ``<select>`` list with the condition (contains/not contains/etc.) and an ``<input>`` or
  ``<textarea>`` to define the comparison value.

Custom Filters
--------------

If your needs are more specific, you can create your own filters. A filter is a
`Symfony Form Type`_ that implements
``EasyCorp\Bundle\EasyAdminBundle\Form\Filter\FilterInterface``. This interface
defines only one method:

.. code-block:: php

    /**
     * $queryBuilder The query builder used in the list action. It's passed to all applied filters
     * $form         The form related to this filter. Use $form->getParent() to access to all filters and their values
     * $metadata     The filter configuration and some extra info related to the entity field if it matches. It's empty
     *               if the filter was created directly in a custom controller (overriding createFiltersForm() method).
     *
     * @return void|false Returns false if the filter wasn't applied
     */
    public function filter(QueryBuilder $queryBuilder, FormInterface $form, array $metadata);

To make things simpler, you can extend from the abstract
``EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Filter`` class. Consider this
example which creates a custom date filter with some special values::

    // src/Form/Filter/DateCalendarFilterType.php
    class DateCalendarFilterType extends FilterType
    {
        public function configureOptions(OptionsResolver $resolver)
        {
            $resolver->setDefaults([
                'choices' => [
                    'Today' => 'today',
                    'This month' => 'this_month',
                    // ...
                ],
            ]);
        }

        public function getParent()
        {
            return ChoiceType::class;
        }

        public function filter(QueryBuilder $queryBuilder, FormInterface $form, array $metadata)
        {
            if ('today' === $form->getData()) {
                // use $metadata['property'] to make this query generic
                $queryBuilder->andWhere('entity.date = :today')
                    ->setParameter('today', (new \DateTime('today'))->format('Y-m-d'));
            }

            // ...
        }
    }

After creating the filter class, update the backend config to associate the new
filter to the field which will use it::

    namespace App\Controller\Admin;

    use App\Form\Filter\DateCalendarFilterType;

    class UserAdminController
    {
        // ...

        public function getIndexPageConfig(): IndexPageConfig
        {
            return IndexPageConfig::new()
                // ...
                ->filters('...', '...', DateCalendarFilter::new('signupDate'));
        }
    }

Unmapped Filters
----------------

By default, each filter must be associated with a property of the entity.
However, sometimes you need to filter by the property of a related entity (e.g.
an ``order`` is associated with a ``customer`` and you want to filter orders by
the ``country`` property of the ``customer``). In those cases, set the
``mapped`` option to ``false`` in the filter or you'll see an exception::

    namespace App\Controller\Admin;

    use App\Form\Filter\CustomerCountryFilterType;

    class UserAdminController
    {
        // ...

        public function getIndexPageConfig(): IndexPageConfig
        {
            return IndexPageConfig::new()
                // ...
                ->filters(
                    '...',
                    '...',
                    // 'country' doesn't exist as a property of 'Order' so it's
                    // defined as 'not mapped' to avoid errors
                    CustomerCountryFilterType::new('country')->mapped(false),
                );
        }
    }

In the custom filter class, you can now add the query related to the associated
entity::

    // App\Form\Filter\CustomerCountryFilterType
    // ...

    public function filter(QueryBuilder $queryBuilder, FormInterface $form, array $metadata)
    {
        if (null !== $form->getData()) {
            $queryBuilder
                ->leftJoin('entity.customer', 'customer')
                ->andWhere('customer.country = :country')
                ->setParameter('country', $form->getData());
        }
    }

.. TODO: explain and show an example of compound filter forms
