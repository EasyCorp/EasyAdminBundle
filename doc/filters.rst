Filters
=======

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
                // most of the time there is no need to define the
                // filter type because EasyAdmin can guess it automatically
                ->add(BooleanFilter::new('published'))
            ;
        }
    }

Built-in Filters
----------------

Common Filter Options
~~~~~~~~~~~~~~~~~~~~~

All built-in filters share these common configuration methods:

* ``setLabel(string|false $label)``: customizes or hides the filter label
* ``setFormType(string $formTypeFqcn)``: changes the form type used to render the filter
* ``setFormTypeOption(string $name, mixed $value)``: sets a single form type option
* ``setFormTypeOptions(array $options)``: sets multiple form type options at once

ArrayFilter
~~~~~~~~~~~

Filters properties that store arrays or collections of values. Applied by
default to Doctrine array fields. Renders a comparison selector (contains/not
contains) and a multi-select input for the values to match::

    use EasyCorp\Bundle\EasyAdminBundle\Filter\ArrayFilter;

    $filters->add(ArrayFilter::new('tags')->setChoices([
        'Tech' => 'tech',
        'News' => 'news',
        'Sports' => 'sports',
    ]));

Options:

* ``setChoices(array $choices)``: defines the available choices to filter by
* ``setTranslatableChoices(array $choices)``: same as above but with translatable labels
* ``canSelectMultiple()`` (``false``): allows selecting multiple values

BooleanFilter
~~~~~~~~~~~~~

Filters boolean properties. Applied by default to boolean fields. Renders two
radio buttons labeled "Yes" and "No"::

    use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;

    $filters->add(BooleanFilter::new('published'));

This filter has no additional options.

ChoiceFilter
~~~~~~~~~~~~

Filters properties against a predefined list of choices. Renders a comparison
selector and a dropdown (or checkboxes/radio buttons) with the available choices::

    use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;

    $filters->add(ChoiceFilter::new('status')->setChoices([
        'Draft' => 'draft',
        'Published' => 'published',
        'Archived' => 'archived',
    ]));

Options:

* ``setChoices(array $choices)``: defines the available choices
* ``setTranslatableChoices(array $choices)``: defines choices with translatable labels
* ``canSelectMultiple()`` (``false``): allows selecting multiple values
* ``renderExpanded()`` (``false``): renders as checkboxes/radio buttons instead of dropdown

ComparisonFilter
~~~~~~~~~~~~~~~~

A generic filter that combines a comparison operator selector with a value input.
Used as the base for other filters. You can use it directly when you need a
simple comparison filter with custom form types::

    use EasyCorp\Bundle\EasyAdminBundle\Filter\ComparisonFilter;

    $filters->add(ComparisonFilter::new('score'));

This filter has no additional options beyond the common ones.

CountryFilter
~~~~~~~~~~~~~

Filters properties that store `ISO 3166-1`_ country codes. Displays a dropdown
with country names translated to the current locale using the
`Symfony Intl component`_::

    use EasyCorp\Bundle\EasyAdminBundle\Filter\CountryFilter;

    $filters->add(CountryFilter::new('country')
        ->includeOnly(['US', 'CA', 'MX'])
        ->preferredChoices(['US'])
    );

Options:

* ``includeOnly(array $countryCodes)`` (``null``): restricts choices to these country codes only
* ``remove(array $countryCodes)`` (``null``): removes these country codes from the choices
* ``preferredChoices(array $countryCodes)`` (``null``): displays these countries at the top of the list
* ``useAlpha3Codes()`` (``false``): uses `ISO 3166-1 alpha-3`_ codes (e.g. ``USA``) instead of `alpha-2`_ (e.g. ``US``)
* ``canSelectMultiple()`` (``false``): allows selecting multiple countries
* ``renderExpanded()`` (``false``): renders as checkboxes instead of dropdown

CurrencyFilter
~~~~~~~~~~~~~~

Filters properties that store `ISO 4217`_ currency codes. Displays a dropdown
with currency names translated to the current locale using the
`Symfony Intl component`_::

    use EasyCorp\Bundle\EasyAdminBundle\Filter\CurrencyFilter;

    $filters->add(CurrencyFilter::new('currency')
        ->includeOnly(['USD', 'EUR', 'GBP', 'JPY'])
    );

Options:

* ``includeOnly(array $currencyCodes)`` (``null``): restricts choices to these currency codes only
* ``remove(array $currencyCodes)`` (``null``): removes these currency codes from the choices
* ``preferredChoices(array $currencyCodes)`` (``null``): displays these currencies at the top of the list
* ``canSelectMultiple()`` (``false``) allows selecting multiple currencies
* ``renderExpanded()`` (``false``): renders as checkboxes instead of dropdown

DateTimeFilter
~~~~~~~~~~~~~~

Filters date and time properties. Applied by default to ``datetime``, ``date``,
and ``time`` fields. Renders a comparison selector (before/after/between/etc.)
and the browser's native date/time picker::

    use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;

    $filters->add(DateTimeFilter::new('createdAt'));

This filter has no additional options. The comparison operators include:
equals, not equals, after, after or on, before, before or on, and between.

EntityFilter
~~~~~~~~~~~~

Filters properties with Doctrine associations (ManyToOne, OneToMany,
ManyToMany, OneToOne). Renders a comparison selector and a dropdown with
related entities::

    use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;

    $filters->add(EntityFilter::new('category'));

    // with autocomplete for large datasets
    $filters->add(EntityFilter::new('author')->autocomplete());

Options:

* ``autocomplete()`` (``false``): loads choices dynamically via AJAX requests
  (recommended for large datasets)
* ``canSelectMultiple()`` (``false``): allows selecting multiple entities

LanguageFilter
~~~~~~~~~~~~~~

Filters properties that store `ISO 639-1`_ language codes. Displays a dropdown
with language names translated to the current locale using the
`Symfony Intl component`_::

    use EasyCorp\Bundle\EasyAdminBundle\Filter\LanguageFilter;

    $filters->add(LanguageFilter::new('language')
        ->includeOnly(['en', 'es', 'fr', 'de', 'it', 'pt'])
    );

Options:

* ``includeOnly(array $languageCodes)`` (``null``): restricts choices to these language codes only
* ``remove(array $languageCodes)`` (``null``): removes these language codes from the choices
* ``preferredChoices(array $languageCodes)`` (``null``): displays these languages at the top of the list
* ``useAlpha3Codes()`` (``false``): uses `ISO 639-2`_ alpha-3 codes (e.g. ``eng``) instead of alpha-2 (e.g. ``en``)
* ``canSelectMultiple()`` (``false``): allows selecting multiple languages
* ``renderExpanded()`` (``false``): renders as checkboxes instead of dropdown

LocaleFilter
~~~~~~~~~~~~

Filters properties that store locale codes. Displays a dropdown with locale
names (language + region) translated to the current locale using the
`Symfony Intl component`_::

    use EasyCorp\Bundle\EasyAdminBundle\Filter\LocaleFilter;

    $filters->add(LocaleFilter::new('locale')
        ->includeOnly(['en_US', 'en_GB', 'es_ES', 'fr_FR', 'de_DE'])
    );

Options:

* ``includeOnly(array $localeCodes)`` (``null``): restricts choices to these locale codes only
* ``remove(array $localeCodes)`` (``null``): removes these locale codes from the choices
* ``preferredChoices(array $localeCodes)`` (``null``): displays these locales at the top of the list
* ``canSelectMultiple()`` (``false``): allows selecting multiple locales
* ``renderExpanded()`` (``false``): renders as checkboxes instead of dropdown

NullFilter
~~~~~~~~~~

Filters properties based on whether they are null or not null. Renders two
radio buttons for the "is null" and "is not null" options. This filter is not
applied automatically to any field::

    use EasyCorp\Bundle\EasyAdminBundle\Filter\NullFilter;

    $filters->add(NullFilter::new('deletedAt'));

Options:

* ``setChoiceLabels(string $nullLabel, string $notNullLabel)``: customizes the
  labels for the radio buttons (default: "Is Null" and "Is Not Null")

NumericFilter
~~~~~~~~~~~~~

Filters numeric properties (integers, floats, decimals). Applied by default to
numeric fields. Renders a comparison selector (equals/greater than/less than/
between/etc.) and one or two number inputs::

    use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;

    $filters->add(NumericFilter::new('price'));

This filter has no additional options. The comparison operators include:
equals, not equals, greater than, greater than or equal, less than, less than
or equal, and between (which shows two inputs for min/max values).

TextFilter
~~~~~~~~~~

Filters string and text properties. Applied by default to string and text
fields. Renders a comparison selector (contains/starts with/ends with/etc.)
and a text input::

    use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;

    $filters->add(TextFilter::new('title'));

This filter has no additional options. The comparison operators include:
contains, not contains, equals, not equals, starts with, ends with, and
is empty/is not empty.

TimezoneFilter
~~~~~~~~~~~~~~

Filters properties that store timezone identifiers. Displays a dropdown with
timezone names (with UTC offsets) translated to the current locale using the
`Symfony Intl component`_::

    use EasyCorp\Bundle\EasyAdminBundle\Filter\TimezoneFilter;

    // show only timezones for a specific country
    $filters->add(TimezoneFilter::new('timezone')->forCountryCode('US'));

Options:

* ``forCountryCode(string $countryCode)`` (``null``): restricts choices to timezones of the given country
* ``includeOnly(array $timezoneIds)`` (``null``): restricts choices to these timezone identifiers only
* ``remove(array $timezoneIds)`` (``null``): removes these timezone identifiers from the choices
* ``preferredChoices(array $timezoneIds)`` (``null``): displays these timezones at the top of the list
* ``canSelectMultiple()`` (``false``): allows selecting multiple timezones
* ``renderExpanded()`` (``false``): renders as checkboxes instead of dropdown

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
all the common methods. That way you only need to implement the ``apply()``
method, which receives the filter form data and the ``QueryBuilder`` to customize
the query.

Consider this example which creates a custom date filter with some special values::

    // src/Controller/Admin/Filter/DateCalendarFilter.php
    namespace App\Controller\Admin\Filter;

    use App\Form\Type\Admin\DateCalendarFilterType;
    use Doctrine\ORM\QueryBuilder;
    use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterInterface;
    use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
    use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
    use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDataDto;
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
        public function configureOptions(OptionsResolver $resolver): void
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
                ->add(CustomerCountryFilter::new('country')->setFormTypeOption('mapped', false))
            ;
        }
    }

.. _`Symfony Intl component`: https://symfony.com/doc/current/components/intl.html
.. _`ISO 3166-1`: https://en.wikipedia.org/wiki/ISO_3166-1
.. _`ISO 3166-1 alpha-3`: https://en.wikipedia.org/wiki/ISO_3166-1_alpha-3
.. _`alpha-2`: https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2
.. _`ISO 4217`: https://en.wikipedia.org/wiki/ISO_4217
.. _`ISO 639-1`: https://en.wikipedia.org/wiki/ISO_639-1
.. _`ISO 639-2`: https://en.wikipedia.org/wiki/ISO_639-2
