Filters
=======

.. raw:: html

    <div class="box box--small box--warning">
        <strong class="title">WARNING:</strong>

        You are browsing the documentation for <strong>EasyAdmin 3.x</strong>,
        which has just been released. Switch to
        <a href="https://symfony.com/doc/2.x/bundles/EasyAdminBundle/index.html">EasyAdmin 2.x docs</a>
        if your application has not been upgraded to EasyAdmin 3 yet.
    </div>

The listings of the ``index`` page can be refined with **filters**, a series of
form controls that add conditions to the query (e.g. ``price > 10``, ``enabled = true``).
Define your filters with the ``configureFilters()`` method of your
:doc:`dashboard </dashboards>` or :doc:`CRUD controller </crud>`::

    namespace App\Controller\Admin;

    use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
    use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

    class ProductCrudController extends AbstractCrudController
    {
        // ...

        public function configureFilters(Filters $filters): Filters
        {
            return $filters
                ->add('title')
                ->add('price')
                ->add('published')
            ;
        }
    }

EasyAdmin provides ready-to-use filters for the most common needs (dates,
numeric values, collections, etc.). The type of filter is automatically selected
based on the data type of the property, but you can also define the filter type
explicitly::

    namespace App\Controller\Admin;

    use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
    use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
    use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;

    class ProductCrudController extends AbstractCrudController
    {
        // ...

        public function configureFilters(Filters $filters): Filters
        {
            return $filters
                ->add('title')
                ->add('price')
                // most of the times there is no need to define the
                // filter type because EasyAdmin can guess it automatically
                ->add(BooleanFilter::new('published'))
            ;
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

If your needs are more specific, you can create your own filters. A filter is
defined using two classes:

* A config class implementing ``EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterInterface``
  is used to configure the filter options and to apply the search conditions
  when the filter is active;
* A form class implementing ``Symfony\Component\Form\FormType`` is used to render
  the HTML widgets used to input the filter data in the application.

You can use the ``FilterTrait`` in your filter config class to avoid implementing
all the commonly methods. That way you only need to implement the ``apply()``
method, which is the one that changes the ``$queryBuilder`` object to apply the
query clauses needed by the filter.

Consider this example which creates a custom date filter with some special values::

    // src/Admin/Filter/DateCalendarFilter.php
    namespace App\Admin\Filter;

    use App\Form\Type\Admin\DateCalendarFilterType;
    use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterInterface;
    use EasyCorp\Bundle\EasyAdminBundle\Filter\FilterTrait;

    class DateCalendarFilter implements FilterInterface
    {
        use FilterTrait;

        public static function new(string $propertyName, $label = null): self
        {
            return (new self())
                ->setFilterFqcn(__CLASS__)
                ->setProperty($propertyName)
                ->setLabel($label)
                ->setFormType(DateCalendarFilterType::class);
        }

        public function apply(QueryBuilder $queryBuilder, FilterDataDto $filterDataDto, ?FieldDto $fieldDto, EntityDto $entityDto): void
        {
             if ('today' === $filterDataDto->getValue()) {
                $queryBuilder->andWhere(sprintf('%s.%s = :today', $filterDataDto->getEntityAlias(), $filterDataDto->getProperty()))
                    ->setParameter('today', (new \DateTime('today'))->format('Y-m-d'));
            }

            // ...
        }
    }

Then, create the associated form type that renders for example a ``<select>``
widget with some predefined values::

    // src/Form/Type/Admin/DateCalendarFilterType.php
    namespace App\Form\Type\Admin;

    use Symfony\Component\Form\AbstractType;
    use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
    use Symfony\Component\OptionsResolver\OptionsResolver;

    class DateCalendarFilterType extends AbstractType
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
    }

You can now use this custom filter in any of your dashboards and CRUD controllers::

    namespace App\Controller\Admin;

    use App\Admin\Filter\DateCalendarFilter;
    use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
    use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
    use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;

    class UserCrudController extends AbstractCrudController
    {
        // ...

        public function configureFilters(Filters $filters): Filters
        {
            return $filters
                // ...
                ->add(DateCalendarFilter::new('signupDate'))
            ;
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

    use App\Admin\Filter\CustomerCountryFilter;
    use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
    use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
    use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;

    class OrderCrudController extends AbstractCrudController
    {
        // ...

        public function configureFilters(Filters $filters): Filters
        {
            return $filters
                // 'country' doesn't exist as a property of 'Order' so it's
                // defined as 'not mapped' to avoid errors
                ->add(CustomerCountryFilter::new('country')->mapped(false))
            ;
        }
    }

.. TODO: explain and show an example of compound filter forms
