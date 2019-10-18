Resources
=========

**Resources** are the Doctrine entities managed in the backend. They are managed
with **resource admins**, which are controllers that implement the *"CRUD
actions"* (create, show, update, delete). Each resource admin can be associated
to one or more dashboards.

Technically, resource admins are regular `Symfony controllers`_ so you can do
anything you usually do in a controller, such as injecting services and calling
to shortcuts like ``$this->render()`` or ``$this->isGranted()``.

Resource admin classes must implement the ``ResourceAdminInterface``, which
ensures that certain methods are defined in the admin. Instead of implementing
the interface, you can also extend from the ``AbstractResourceAdminController``
class. Run the following command to generate the basic structure of a resource admin:

.. code-block:: terminal

    $ php bin/console make:admin:resource

Resource Admin Actions
----------------------

The three main actions of the resource admins are:

* ``index``, displays a list of entities which can be paginated, sorted by
  column and refined with search queries and filters;
* ``detail``, displays the contents of a given entity;
* ``form``, allows to edit and create entities.

These actions correspond to the three most common backend pages, but the
``AbstractResourceAdmin`` controller defines other secondary actions such as
``delete`` and ``autocomplete``.

The default behavior of these actions in the ``AbstractResourceAdminController``
is appropriate for most backends, but you can customize it in several ways:

* Override the entire action method in your resource admin controller;
* Listen to any of the :doc:`events triggered by EasyAdmin </events>` during the
  execution of the request;
* Use the ``addTemplateParameters(string $action)`` method to pass custom
  parameters to the Twig template rendered by the action and
  :ref:`use a custom template <template-customization>`.

Resource Admin Configuration
----------------------------

The ``getResourceConfig()`` method configures some basic options about the
entity being managed::

    namespace App\Controller\Admin;

    use App\Entity\Product;
    use EasyCorp\Bundle\EasyAdminBundle\Config\ResourceConfig;
    use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractResourceAdminController;

    class ProductAdminController extends AbstractResourceAdminController
    {
        // ...

        public function getResourceConfig(): ResourceConfig
        {
            return ResourceConfig::new()
                // the fully-qualified class name (FQCN) of the Doctrine entity
                // managed by this resource admin
                ->entityClass(Product::class)

                // the labels used to refer to this entity in titles, buttons, etc.
                ->labelInSingular('Product')
                ->labelInPlural('Products')

                // formats applied to date/time and number values in all the resource admins
                // managed by this dashboard (it can be overridden per resource admin)
                ->dateFormat('...')
                ->timeFormat('...')
                ->dateTimeFormat('...')
                ->dateIntervalFormat('%%y Year(s) %%m Month(s) %%d Day(s)')
                ->numberFormat('%.2d');
        }
    }

.. note::

    In addition to the above configuration options, there are other options
    related to :doc:`actions </actions>` which are explained in the article
    dedicated to that feature.

Index Page
----------

The ``index`` page is rendered executing the ``index()`` method of the controller.
You can configure this page with the method ``getIndexPageConfig()``::

    namespace App\Controller\Admin;

    use EasyCorp\Bundle\EasyAdminBundle\Config\IndexPageConfig;
    use EasyCorp\Bundle\EasyAdminBundle\Config\IndexPageConfig;
    use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractResourceAdminController;

    class ProductAdminController extends AbstractResourceAdminController
    {
        // ...

        public function getIndexPageConfig(): IndexPageConfig
        {
            return IndexPageConfig::new()
                // the visible title at the top of the page and the content of the <title> element
                // it can include these placeholders: %entity_id%, %entity_label_singular%, %entity_label_plural%
                ->title('%entity_label_plural%')

                // the maximum number of results to display in the paginated listings (default: 15)
                ->maxResults(30)

                // the help message displayed to end users (it can contain HTML tags)
                ->help('...')

                // the names of the Doctrine entity properties where the search is made on
                ->searchFields(['name', 'description'])
                // use dots (e.g. 'seller.email') to search in Doctrine associations
                ->searchFields(['name', 'description', 'seller.email', 'seller.phone'])
                // set it to null to disable and hide the search box
                ->searchFields(null);
        }
    }

.. note::

    In addition to the above configuration options, there are other options
    related to :doc:`actions </actions>`, :doc:`filters </filters>` and
    :doc:`security </security>` which are explained in the articles dedicated
    to those features.

.. note::

    When using `Doctrine filters`_, listings may not include some items because
    they were removed by those global Doctrine filters. Use the dashboard route
    name to not apply the filters when the request URL belongs to the dashboard
    You can also get the dashboard route name via the :ref:`application context variable <application-context>`.

The default Doctrine query executed to get the list of entities displayed in the
``index`` page takes into account the sorting configuration, the optional search
query, the optional :doc:`filters </filters>` and the pagination. If you need to
fully customize this query, override the ``createIndexQuery()`` method in your
resource admin controller.

Detail Page
-----------

The ``detail`` page is rendered executing the ``detail()`` method of the controller.
You can configure this page with the method ``getDetailPageConfig()``::

    namespace App\Controller\Admin;

    use EasyCorp\Bundle\EasyAdminBundle\Config\DetailPageConfig;
    use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractResourceAdminController;

    class ProductAdminController extends AbstractResourceAdminController
    {
        // ...

        public function getDetailPageConfig(): DetailPageConfig
        {
            return DetailPageConfig::new()
                // the visible title at the top of the page and the content of the <title> element
                // it can include these placeholders: %entity_id%, %entity_label_singular%, %entity_label_plural%
                ->title('%entity_label_singular% <span class="text-muted text-small">(#%entity_id%)</span>')

                // the help message displayed to end users (it can contain HTML tags)
                ->help('...');
        }
    }

.. note::

    In addition to the above configuration options, there are other options
    related to :doc:`actions </actions>` and :doc:`security </security>` which
    are explained in the articles dedicated to those features.

Form Page
---------

The ``form`` page is rendered executing the ``form()`` method of the controller.
You can configure this page with the method ``getFormPageConfig()``::

    namespace App\Controller\Admin;

    use EasyCorp\Bundle\EasyAdminBundle\Config\FormPageConfig;
    use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractResourceAdminController;

    class ProductAdminController extends AbstractResourceAdminController
    {
        // ...

        // If the argument of this method is TRUE, the form is editing an existing
        // entity; otherwise, the form is creating a new entity
        public function getFormPageConfig(bool $isEditForm): FormPageConfig
        {
            return FormPageConfig::new()
                // the visible title at the top of the page and the content of the <title> element
                // it can include these placeholders: %entity_id%, %entity_label_singular%, %entity_label_plural%
                ->title($isEditForm
                    ? 'Edit %entity_label_singular% <span class="text-muted">#%entity_id%</span>'
                    : 'Create %entity_label_singular%')

                // the help message displayed to end users (it can contain HTML tags)
                ->help('The upload process can take a lot of time (don\'t close the browser window)')

                // configures the three save buttons of the edit/new form
                ->addSaveAndReturnToDetail(true)
                ->addSaveAndReturnToList(true)
                ->addSaveAndAddAnother(false)

                // the theme/themes to use when rendering the forms of this entity
                // it overrides the form themes set in the dashboard (if any)
                ->formThemes('foo.html.twig')

                // options passed as second argument when creating the form with createFormBuilder()
                ->formOptions([
                    'validation_groups' => ['Default', 'my_validation_group']
                ]);
        }
    }

.. note::

    In addition to the above configuration options, there are other options
    related to :doc:`actions </actions>` which are explained in the article
    dedicated to that feature.

Fields
------

Fields are the Doctrine entity properties displayed in the ``index``, ``detail``
and ``form`` pages. You can display the same fields on all pages or, more
commonly, display a few fields in the ``index`` page and all/most fields in the
other pages. The ``getFields()`` method configures them::

    namespace App\Controller\Admin;

    use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractResourceAdminController;

    class ProductAdminController extends AbstractResourceAdminController
    {
        // ...

        public function getFields(string $action): iterable
        {
            return [
                IdField::new('id'),
                TextField::new('firstName'),
                TextField::new('lastName'),
                TextField::new('phone'),
                EmailField::new('email'),
                DateTimeField::new('createdAt'),
            ];
        }
    }

The only mandatory argument of the ``Field::new()`` constructor is the name of
the Doctrine entity property managed by this field. The value of the properties
is read using the `PropertyAccess component`_, so the entity can define them as
public properties (e.g. ``public $firstName``) or public methods (e.g.
``public function getFirstName()``, ``public function firstName()``).

The second optional argument of the ``Field::new()`` constructor is the visible
label (if undefined, it's autogenerated from the property name)::

    public function getFields(string $action): iterable
    {
        return [
            // not defining the label explicitly or setting it to NULL means
            // that the label is autogenerated (e.g. 'firstName' -> 'First Name')
            TextField::new('firstName'),
            TextField::new('firstName', null),
            // set the label explicitly to display exactly that label
            TextField::new('firstName', 'Name'),
            // set the label to FALSE to not display any label for this field
            TextField::new('firstName', false),
            // ...
        ];
    }

Unmapped Fields
~~~~~~~~~~~~~~~

Fields usually reference to properties of the related Doctrine entity. However,
they can also refer to methods of the entity which are not mapped to properties.
For example, if your ``Customer`` entity defines the ``firstName`` and
``lastName`` properties, you may want to display in the ``index`` page a single
column called ``Name`` with both values merged.

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
conversion between property names and methods must comply with the rules of the
`PropertyAccess component`_ (e.g. ``foo_bar`` -> ``getFooBar()`` or ``fooBar()``)::

    public function getFields(string $action): iterable
    {
        return [
            TextField::new('fullName', 'Name'),
            // ...
        ];
    }

The main limitation of unmapped fields is that they are not sortable because
they cannot be included in the Doctrine query.

Field Configuration Options
~~~~~~~~~~~~~~~~~~~~~~~~~~~

In addition to the two ``::new()`` arguments, each field defines several
configuration options. These are the options common to all fields::

    TextField::new('firstName', 'Name')
        // CSS class or classes applied to the field contents ('index' page) or to
        // the row that displays the contents ('detail' and 'form' pages)
        ->cssClass('text-large text-bold')

        // custom Twig template used to render this field in 'index' and 'detail'
        // (this is not used in the 'form' action, which uses Symfony Forms)
        ->template('admin/fields/my_template.html.twig')

        // is TRUE, listing can be sorted by this field (default: TRUE)
        // unmapped fields and Doctrine associations cannot be sorted
        ->sortable(false)

        // only applied to 'index' page. Useful for example to right-align numbers
        ->textAlign('right')

        // help message displayed in the 'form' page for this field
        ->help('...')

        // this option is ignored for properties which are not date/time or numeric
        // it overrides the global date/time or number formatting defined in the
        // dashboard or the resource admin.
        ->format('...')

        // the Symfony Form type used to render this field in 'form' page
        // (fields have good default values for this option, so you don't usually configure this)
        ->formType(TextType::class)

        // an array of parameters passed to the Symfony form type
        ->formTypeOptions(['name' => 'value'])

        // the Symfony security role the user must have to see this field
        // (it's explained in detail in the article about security)
        ->permission('ROLE_ADMIN');

Check out the article about :doc:`EasyAdmin Fields Reference </fields>` to know
about the specific options of each field.

Fields Layout
~~~~~~~~~~~~~

For simple backends you will probably display the same fields in all pages
(``index``, ``detail`` and ``form``). But for more complex backends, you'll
need to hide/show some fields depending on the current page. You have several
ways to achieve this.

First, you have some utility methods to display the fields conditionally::

    public function getFields(string $action): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('firstName'),
            TextField::new('lastName'),
            PasswordField::new('password')->onlyWhenUpdating(),
            TextField::new('phone'),
            EmailType::new('email')->hideOnIndex(),
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
* ``onlyWhenCreating()`` (``form`` page when creating a new entity)
* ``onlyWhenUpdating()`` (``form`` page when updating an existing entity)

If the field layout is completely different on each page, consider using the
given ``$action`` argument to differentiate them::

    public function getFields(string $action): iterable
    {
        $id = IdField::new('id');
        $firstName = TextField::new('firstName');
        $lastName = TextField::new('lastName');
        $password = PasswordField::new('password')->onlyWhenUpdating();
        $phone = TextField::new('phone');
        $email = EmailType::new('email');
        $createdAt = DateTimeField::new('createdAt');

        if ('index' === $action) {
            return [$id, $firstName, $lastName, $phone];
        } elseif('detail' === $action) {
            return ['...'];
        } else {
            return ['...'];
        }
    }

If you need greater control, consider using the following way of defining the
fields::

    public function getFields(string $action): iterable
    {
        yield IdField::new('id')->hideOnForm();

        if ('... some expression ...') {
            yield TextField::new('firstName');
            yield TextField::new('lastName');
        }

        yield PasswordField::new('password')->onlyWhenUpdating();
        yield TextField::new('phone');
        yield EmailType::new('email')->hideOnIndex();
        yield DateTimeField::new('createdAt')->onlyOnDetail();
    }

You can also group fields in tabs, columns and panels, as explained below.

Field Sections
~~~~~~~~~~~~~~

It introduces a visual separation between fields, which is useful for long forms::

    public function getFields(string $action): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),

            // sections usually display only a title
            FormSectionField::new('User Details'),
            TextField::new('firstName'),
            TextField::new('lastName'),

            // sections without titles only display a separation between fields
            FormSectionField::new(),
            PasswordField::new('password')->onlyWhenUpdating(),
            DateTimeField::new('createdAt')->onlyOnDetail(),

            // sections can also define their icon, CSS class and help message
            FormSectionField::new('Contact information')
                ->icon('phone')->cssClass('optional')->help('Phone number is preferred'),
            TextField::new('phone'),
            EmailType::new('email')->hideOnIndex(),
        ];
    }

Field Groups
~~~~~~~~~~~~

It groups one or more fields using ``<fieldset>`` HTML elements to visually
separate them from the rest of the form fields::

    public function getFields(string $action): iterable
    {
        return [
            // don't pass any argument if you prefer to not display the group title
            FormGroupField::new('Basic information'),
            IdField::new('id')->hideOnForm(),
            TextField::new('firstName'),
            TextField::new('lastName'),

            // groups can also define their icon, CSS class and help message
            FormGroupField::new('Contact information')
                ->icon('phone')->cssClass('optional')->help('Phone number is preferred'),
            TextField::new('phone'),
            EmailType::new('email')->hideOnIndex(),
        ];
    }

Field Columns
~~~~~~~~~~~~~

It divides the whole page width into multiple columns where you can display the
fields. You can include sections and groups inside columns, to create advanced
form layouts::

    public function getFields(string $action): iterable
    {
        return [
            // the mandatory argument is the width of the column (12 = whole page width)
            FormColumnField::new(4),
            IdField::new('id')->hideOnForm(),
            TextField::new('firstName'),
            TextField::new('lastName'),

            // the only extra option defined for field columns is the CSS class
            FormColumnField::new(6)->cssClass('highlight'),
            TextField::new('phone'),
            EmailType::new('email')->hideOnIndex(),
        ];
    }

Field Tabs
~~~~~~~~~~

This element groups one or more fields and displays them in a separate tab. You
can combine it with the other elements (tabs can contain columns, groups and
sections , but no the other way around)::

    public function getFields(string $action): iterable
    {
        return [
            // the argument is the title of the tab
            FormTabField::new('Basic Information'),
            IdField::new('id')->hideOnForm(),
            TextField::new('firstName'),
            TextField::new('lastName'),

            // tabs can also define their icon, CSS class and help message
            FormTabField::new('Contact information')
                ->icon('phone')->cssClass('optional')->help('Phone number is preferred'),
            TextField::new('phone'),
            EmailType::new('email')->hideOnIndex(),
        ];
    }

.. _`How to Create a Custom Form Field Type`: https://symfony.com/doc/current/cookbook/form/create_custom_field_type.html
.. _`Symfony Form types`: https://symfony.com/doc/current/reference/forms/types.html
.. _`PropertyAccess component`: https://symfony.com/doc/current/components/property_access.html
.. _`customize individual form fields`: https://symfony.com/doc/current/form/form_customization.html#how-to-customize-an-individual-field
.. _`form fragment naming rules`: https://symfony.com/doc/current/form/form_themes.html#form-template-blocks
.. _`override any part of third-party bundles`: https://symfony.com/doc/current/bundles/override.html
.. _`Trix editor`: https://trix-editor.org/
.. _`Symfony security voters`: https://symfony.com/doc/current/security/voters.html
.. _`form data transformer`: https://symfony.com/doc/current/form/data_transformers.html
.. _`Doctrine filters`: https://www.doctrine-project.org/projects/doctrine-orm/en/current/reference/filters.html
