Fields
======

.. raw:: html

    <div class="box box--small box--warning">
        <strong class="title">WARNING:</strong>

        You are browsing the documentation for <strong>EasyAdmin 3.x</strong>,
        which has just been released. Switch to
        <a href="https://symfony.com/doc/2.x/bundles/EasyAdminBundle/index.html">EasyAdmin 2.x docs</a>
        if your application has not been upgraded to EasyAdmin 3 yet.
    </div>

Fields allow to display the contents of your Doctrine entities on each
:ref:`CRUD page <crud-pages>`. EasyAdmin provides built-in fields to display
all the common data types, but you can also :ref:`create your own fields <custom-fields>`.

Configuring the Fields to Display
---------------------------------

If your :doc:`CRUD controller </crud>` extends from the ``AbstractCrudController``
provided by EasyAdmin, the fields are configured automatically. In the ``index``
page you'll see a few fields and in the rest of pages you'll see as many fields
as needed to display all the properties of your Doctrine entity.

Implement the ``configureFields()`` method in your CRUD controller to customize
the list of fields to display::

    namespace App\Controller\Admin;

    use App\Entity\Product;
    use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

    class ProductCrudController extends AbstractCrudController
    {
        public static function getEntityFqcn(): string
        {
            return Product::class;
        }

        public function configureFields(string $pageName): iterable
        {
            // ...
        }

        // ...
    }

There are several ways of defining the list of fields to display.

**Option 1.** Return strings with the name of the properties to display. EasyAdmin
creates fields automatically for them and applies the default config options::

    public function configureFields(string $pageName): iterable
    {
        return [
            'title',
            'description',
            'price',
            'stock',
            'publishedAt',
        ];
    }

**Option 2.** Return ``Field`` objects created for the Doctrine entity properties.
EasyAdmin transforms these generic ``Field`` objects into the specific objects
used to display each type of property::

    use EasyCorp\Bundle\EasyAdminBundle\Field\Field;

    public function configureFields(string $pageName): iterable
    {
        return [
            Field::new('title'),
            Field::new('description'),
            Field::new('price'),
            Field::new('stock'),
            Field::new('publishedAt'),
        ];
    }

**Option 3.** Return the appropriate field objects to display each property::

    use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
    use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
    use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
    use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
    use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('title'),
            TextEditorField::new('description'),
            MoneyField::new('price')->setCurrency('EUR'),
            IntegerField::new('stock'),
            DateTimeField::new('publishedAt'),
        ];
    }

The only mandatory argument of the field constructors is the name of the
Doctrine entity property managed by this field. EasyAdmin uses the
`PropertyAccess component`_ to get the value of the properties, so the entity
can define their access as public properties (e.g. ``public $firstName``) or
public methods (e.g. ``public function getFirstName()``, ``public function
firstName()``).

.. note::

    EasyAdmin uses Symfony Forms to create and edit Doctrine entities. That's
    why all entity properties must be nullable: their setters need to accept
    ``null`` values and their getters must be allowed to return ``null``. In the
    database, the associated fields don't have to be nullable.

Unmapped Fields
~~~~~~~~~~~~~~~

Fields usually reference to properties of the related Doctrine entity. However,
they can also refer to methods of the entity which are not associated to any
properties. For example, if your ``Customer`` entity defines the ``firstName``
and ``lastName`` properties, you may want to display a "Full Name" field with
both values merged.

To do so, add the following method to the entity::

    use Doctrine\ORM\Mapping as ORM;

    /** @ORM\Entity */
    class Customer
    {
        // ...

        public function getFullName()
        {
            return $this->getFirstName().' '.$this->getLastName();
        }
    }

Now, add a ``fullName`` field that refers to this ``getFullName()`` method. The
conversion between field names and methods must comply with the rules of the
`PropertyAccess component`_ (e.g. ``foo_bar`` -> ``getFooBar()`` or ``fooBar()``)::

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('fullName'),
            // ...
        ];
    }

The main limitation of unmapped fields is that they are not sortable because
they cannot be included in the Doctrine query.

Displaying Different Fields per Page
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

There are several methods to display fields conditionally depending on the
current page::

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('firstName'),
            TextField::new('lastName'),
            TextField::new('phone'),
            EmailField::new('email')->hideOnIndex(),
            DateTimeField::new('createdAt')->onlyOnDetail(),
        ];
    }

These are all the available methods:

* ``hideOnDetail()``
* ``hideOnForm()``
* ``hideOnIndex()``
* ``onlyOnDetail()``
* ``onlyOnForms()``
* ``onlyOnIndex()``
* ``onlyWhenCreating()``
* ``onlyWhenUpdating()``

If the fields to display are completely different on each page, use the given
``$pageName`` argument to differentiate them::

    use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

    public function configureFields(string $pageName): iterable
    {
        $id = IdField::new('id');
        $firstName = TextField::new('firstName');
        $lastName = TextField::new('lastName');
        $phone = TextField::new('phone');
        $email = EmailField::new('email');
        $createdAt = DateTimeField::new('createdAt');

        if (Crud::PAGE_INDEX === $pageName) {
            return [$id, $firstName, $lastName, $phone];
        } elseif(Crud::PAGE_DETAIL === $pageName) {
            return ['...'];
        } else {
            return ['...'];
        }
    }

If you need even greater control, consider using the following way of defining
the fields using `PHP generators`_::

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();

        if ('... some expression ...') {
            yield TextField::new('firstName');
            yield TextField::new('lastName');
        }

        yield TextField::new('phone');
        yield EmailField::new('email')->hideOnIndex();
        yield DateTimeField::new('createdAt')->onlyOnDetail();
    }

Field Layout
------------

In pages where you display lots of fields, you can divide them in groups using
the "panels" created with the special ``FormField`` object::

    use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),

            // panels usually display only a title
            FormField::addPanel('User Details'),
            TextField::new('firstName'),
            TextField::new('lastName'),

            // panels without titles only display a separation between fields
            FormField::addPanel(),
            DateTimeField::new('createdAt')->onlyOnDetail(),

            // panels can also define their icon, CSS class and help message
            FormField::addPanel('Contact information')
                ->setIcon('phone')->addCssClass('optional')
                ->setHelp('Phone number is preferred'),
            TextField::new('phone'),
            TextField::new('email')->hideOnIndex(),
        ];
    }

Field Types
-----------

These are all the built-in fields provided by EasyAdmin:

.. class:: list-config-options list-config-options--complex

* ``ArrayField``
* ``AssociationField``
* ``AvatarField``
* ``BooleanField``
* ``ChoiceField``
* ``CodeEditorField``
* ``CollectionField``
* ``ColorField``
* ``CountryField``
* ``CurrencyField``
* ``DateField``
* ``DateTimeField``
* ``EmailField``
* ``IdField``
* ``ImageField``
* ``IntegerField``
* ``LanguageField``
* ``LocaleField``
* ``MoneyField``
* ``NumberField``
* ``PercentField``
* ``SlugField``
* ``TelephoneField``
* ``TextareaField``
* ``TextEditorField``
* ``TextField``
* ``TimeField``
* ``TimezoneField``
* ``UrlField``

Field Configuration
-------------------

This section shows the config options available for all field types. In addition,
some fields define additional config options, as shown in the
:ref:`fields reference <fields_reference>`.

Label Options
~~~~~~~~~~~~~

The second optional argument of the field constructors is the label::

    // not defining the label explicitly or setting it to NULL means
    // that the label is autogenerated (e.g. 'firstName' -> 'First Name')
    TextField::new('firstName'),
    TextField::new('firstName', null),

    // set the label explicitly to display exactly that label
    TextField::new('firstName', 'Name'),

    // set the label to FALSE to not display any label for this field
    TextField::new('firstName', false),

Design Options
~~~~~~~~~~~~~~

::

    TextField::new('firstName', 'Name')
        // CSS class/classes are applied to the field contents (in the 'index' page)
        // or to the row that wraps the contents (in the 'detail', 'edit' and 'new' pages)

        // use this method to add new classes to the ones applied by EasyAdmin
        ->addCssClass('text-large text-bold')
        // use this other method if you want to remove any CSS class added by EasyAdmin
        ->setCssClass('text-large text-bold')

        // this defines the Twig template used to render this field in 'index' and 'detail' pages
        // (this is not used in the 'edit'/'new' pages because they use Symfony Forms themes)
        ->setTemplatePath('admin/fields/my_template.html.twig')

        // only applied to 'index' page. Useful for example to right-align numbers
        ->setTextAlign('right')

Formatting Options
~~~~~~~~~~~~~~~~~~

The ``formatValue()`` method allows to apply a PHP callable to the value before
rendering it in the ``index`` and ``detail`` pages::

    TextField::new('firstName', 'Name')
        // callbacks usually take only the current value as argument...
        ->formatValue(function ($value) {
            return $value < 10 ? sprintf('%d **LOW STOCK**', $value) : $value;
        })

        // ...but callables also receives the entire entity instance as the second argument
        ->formatValue(function ($value, $entity) {
            return $entity->isPublished() ? $value : 'Coming soon...';
        })

        // in PHP 7.4 and newer you can use arrow functions
        // ->formatValue(fn ($value) => $value < 10 ? sprintf('%d **LOW STOCK**', $value) : $value)
        // ->formatValue(fn ($value, $entity) => $entity->isPublished() ? $value : 'Coming soon...')

Misc. Options
~~~~~~~~~~~~~

::

    TextField::new('firstName', 'Name')
        // if TRUE, listing can be sorted by this field (default: TRUE)
        // unmapped fields and Doctrine associations cannot be sorted
        ->setSortable(false)

        // help message displayed for this field in the 'detail', 'edit' and 'new' pages
        ->setHelp('...')

        // the Symfony Form type used to render this field in 'edit'/'new' pages
        // (fields have good default values for this option, so you don't usually configure this)
        ->setFormType(TextType::class)

        // an array of parameters passed to the Symfony form type
        ->setFormTypeOptions(['option_name' => 'option_value'])

.. _fields_reference:

Fields Reference
----------------

.. note::

    This section is not ready yet. We're working on it. Meanwhile, you can rely
    on your IDE auto-completion to discover all the config options of each field.

.. _custom-fields:

Creating Custom Fields
----------------------

A field is a class that implements
``EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface``. Although the
interface only requires to implement a few methods, you may want to add all the
methods available in built-in fields to configure all the common field options.
You can use the ``EasyCorp\Bundle\EasyAdminBundle\Field\FieldTrait`` for that.

Imagine that you want to create a custom ``MapField`` that renders a full map
for a given postal address. This is the class you could create for the field::

    namespace App\Admin\Field;

    use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
    use EasyCorp\Bundle\EasyAdminBundle\Field\FieldTrait;
    use Symfony\Component\Form\Extension\Core\Type\TextareaType;

    final class MapField implements FieldInterface
    {
        use FieldTrait;

        public static function new(string $propertyName, ?string $label = null): self
        {
            return (new self())
                ->setProperty($propertyName)
                ->setLabel($label)
                // this template is used in 'index' and 'detail' pages
                ->setTemplatePath('admin/field/map.html.twig')
                // this is used in 'edit' and 'new' pages to edit the field contents
                // you can use your own form types too
                ->setFormType(TextareaType::class)
                ->addCssClass('field-map')
                // these methods allow to define the web assets loaded when the
                // field is displayed in any CRUD page (index/detail/edit/new)
                ->addCssFiles('js/admin/field-map.css')
                ->addJsFiles('js/admin/field-map.js')
            ;
        }
    }

Next, create the template used to render the field in the ``index`` and ``detail``
:ref:`CRUD pages <crud-pages>`. The template can use any `Twig templating features`_
and the following variables:

* ``ea``, a ``EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext``
  instance which stores the :ref:`admin context <admin-context>` and it's
  available in all backend templates;
* ``field``, a ``EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto``
  instance which stores the config and value of the field being rendered;
* ``entity``, a ``EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto``
  instance which stores the instance of the entity which the field belongs to
  and other useful data about that Doctrine entity.

.. note::

    This template is not used in the ``edit`` and ``new`` :ref:`CRUD pages <crud-pages>`,
    which use `Symfony Form themes`_ to define how each form field is displayed.

That's all. You can now use this field in any of your CRUD controllers::

    use App\Admin\MapField;

    public function configureFields(string $pageName): iterable
    {
        return [
            // ...
            MapField::new('shipAddress'),
        ];
    }

Custom Options
~~~~~~~~~~~~~~

If your field is configurable in any way, you can add custom options for it.
The recommended way of adding options is defining their names as public constants
in the field object and use the ``setCustomOption()`` method defined in the
``FieldTrait`` to set their values.

Imagine that the ``MapField`` defined in the previous section allows to use
either Google Maps or OpenStreetMap to render the maps. You can add that
option as follows::

    namespace App\Admin\Field;

    use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
    use Symfony\Component\Form\Extension\Core\Type\TextareaType;

    final class MapField implements FieldInterface
    {
        use FieldTrait;

        public const OPTION_MAP_PROVIDER = 'mapProvider';

        public static function new(string $propertyName, ?string $label = null): self
        {
            return (new self())
                // ...
                ->setCustomOption(self::OPTION_MAP_PROVIDER, 'openstreetmap')
            ;
        }

        public function useGoogleMaps(): self
        {
            $this->setCustomOption(self::OPTION_MAP_PROVIDER, 'google');

            return $this;
        }

        public function useOpenStreetMap(): self
        {
            $this->setCustomOption(self::OPTION_MAP_PROVIDER, 'openstreetmap');

            return $this;
        }
    }

Later you can access these options via the ``getCustomOptions()`` method of the
field DTO. For example, in a Twig template:

.. code-block:: twig

    {# admin/field/map.html.twig #}
    {% if 'google' === field.customOptions.get('mapProvider') %}
        {# ... #}
    {% endif %}

    {# if you defined the field options as public constants, you can access
       them in the template too (although resulting code is a bit verbose) #}
    {% set map_provider_option = constant('App\\Admin\\MapField::OPTION_MAP_PROVIDER') %}
    {% if 'google' === field.customOptions.get(map_provider_option) %}
        {# ... #}
    {% endif %}

Field Configurators
-------------------

Some default options of some fields depend on the value of the of the entity
property, which is only available during runtime. That's why you can optionally
define a **field configurator**, which is a class that updates the config of the
field before rendering them.

EasyAdmin defines lots of configurators for its built-in fields. You can create
your own configurators too (either to configure your own fields and/or the
built-in fields). Field configurators are classes that implement
``EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface``.

Once implemented, define a Symfony service for your configurator and tag it with
the ``ea.field_configurator`` tag. Optionally you can define the ``priority``
attribute of the tag to run your configurator before or after the built-in ones.

.. _`PropertyAccess component`: https://symfony.com/doc/current/components/property_access.html
.. _`PHP generators`: https://www.php.net/manual/en/language.generators.overview.php
.. _`Twig templating features`: https://twig.symfony.com/doc/3.x/
.. _`Symfony Form themes`: https://symfony.com/doc/current/form/form_themes.html
