Tests
=====

As EasyAdmin is based on Symfony, you can add functional tests for admin pages by
extending the ``WebTestCase`` class and using the `Symfony functional testing workflow`_.

However, as EasyAdmin uses specific ways of displaying the data in its
CRUD pages, a custom test class is provided: ``AbstractCrudTestCase``. The
class is based on traits that define custom asserts and helpers:

#. `Functional Test Case Example`_
#. `URL Generation`_
#. `Actions`_
#. `Asserts`_
#. `Selector Helpers`_


Functional Test Case Example
----------------------------

Suppose you have a :doc:`Dashboard </dashboards>` named ``App\Controller\Admin\AppDashboardController``
and a ``Category`` :doc:`Crud Controller </crud>` named ``App\Controller\Admin\CategoryCrudController``.
Here's an example of a functional test class for that controller.

First, your test class needs to extend the ``AbstractCrudTestCase``::

    // tests/Admin/Controller/CategoryCrudControllerTest.php
    namespace App\Tests\Admin\Controller;

    use App\Controller\Admin\AppDashboardController;
    use App\Controller\Admin\CategoryCrudController;
    use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;

    final class CategoryCrudControllerTest extends AbstractCrudTestCase
    {
        protected function getControllerFqcn(): string
        {
            return CategoryCrudController::class;
        }

        protected function getDashboardFqcn(): string
        {
            return AppDashboardController::class;
        }

        public function testIndexPage(): void
        {
            // this example doesn't use security; in your application you may
            // need to ensure that the user is logged in before the test runs
            $this->client->request('GET', $this->generateIndexUrl());
            static::assertResponseIsSuccessful();
        }
    }

URL Generation
--------------

The ``CrudTestUrlGeneration`` trait, used by ``AbstractCrudTestCase``, generates
EasyAdmin URLs.

.. note::

    The trait can be used on its own but, in that case, the class that is using
    it needs a class property ``adminUrlGenerator`` (an instance of
    ``EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGeneratorInterface``) and
    either of the following:

    * to define two methods ``getControllerFqcn()`` and ``getDashboardFqcn()``;
    * to pass the DashboardFqcn (class name) and ControllerFqcn (class name) as
      input to the URL generation functions.

Here is the list of URL generation functions. All of them build URLs
based on the provided Dashboard and Controller class names:

* ``getCrudUrl()``: the main method; it generates any admin URL and accepts all
  available options;
* ``generateIndexUrl()``: generates the URL for the index page (based on the
  Dashboard and Controller defined);
* ``generateNewFormUrl()``: generates the URL for the New form page (based on
  the Dashboard and Controller defined);
* ``generateEditFormUrl()``: generates the URL for the Edit form page of a
  specific entity (based on the Dashboard and Controller defined and the entity ID);
* ``generateDetailUrl()``: generates the URL for the Detail page of a specific
  entity (based on the Dashboard and Controller defined and the entity ID);
* ``generateFilterRenderUrl()``: generates the URL to get the rendering of the
  filters (based on the Dashboard and Controller defined).

Actions
-------

Used by the ``AbstractCrudTestCase``, the ``CrudTestActions`` trait provides
helpers to interact with the actions displayed in the backend:

* ``clickOnIndexGlobalAction()``: clicks on the
  :ref:`global action <global-actions>` with the given name in the page that the
  test client has just loaded.

.. note::

    The trait can be used on its own but, in that case, the class that is using
    it needs a class property ``client``: instance of
    ``Symfony\Bundle\FrameworkBundle\KernelBrowser``. The trait also includes
    the ``CrudTestSelectors`` trait to locate the actions.

Asserts
-------

``AbstractCrudTestCase`` uses two traits that provide asserts specific to
EasyAdmin:

* ``CrudTestIndexAsserts``: providing asserts for the index page of EasyAdmin;
* ``CrudTestFormAsserts``: providing asserts for the form page of EasyAdmin.

.. note::

    The trait can be used on its own but, in that case, the class that is using
    it needs both:

    * a class property ``client``: instance of ``Symfony\Bundle\FrameworkBundle\KernelBrowser``
    * a class property ``entityManager``: instance of ``Doctrine\ORM\EntityManagerInterface``

CrudTestIndexAsserts
~~~~~~~~~~~~~~~~~~~~

As EasyAdmin uses specific layout, the goal of these asserts is to ease the way
you're testing your EasyAdmin backend by providing specific asserts linked to
the :ref:`index page <crud-pages>`.

The following asserts are provided:

* ``assertIndexFullEntityCount()``
* ``assertIndexPageEntityCount()``
* ``assertIndexPagesCount()``
* ``assertIndexEntityActionExists()``
* ``assertIndexEntityActionNotExists()``
* ``assertIndexEntityActionTextSame()``
* ``assertIndexEntityActionNotTextSame()``
* ``assertGlobalActionExists()``
* ``assertGlobalActionNotExists()``
* ``assertGlobalActionDisplays()``
* ``assertGlobalActionNotDisplays()``
* ``assertIndexColumnExists()``
* ``assertIndexColumnNotExists()``
* ``assertIndexColumnHeaderContains()``
* ``assertIndexColumnHeaderNotContains()``

The ``assertGlobalAction*()`` asserts refer to the
:ref:`global actions <global-actions>` displayed above the listing.

CrudTestFormAsserts
~~~~~~~~~~~~~~~~~~~

As EasyAdmin uses specific layout, the goal of these asserts is to ease the way
you're testing your EasyAdmin backend by providing specific asserts linked to
the **form** (new or edit) page.

The following asserts are provided for the :doc:`fields </fields>` displayed in
those forms:

* ``assertFormFieldExists()``
* ``assertFormFieldNotExists()``
* ``assertFormFieldHasLabel()``
* ``assertFormFieldNotHasLabel()``

Selector Helpers
----------------

Used by the Asserts to locate elements, the ``CrudTestSelectors`` trait defines
a set of selector helpers tailored to EasyAdmin layout.

.. note::

    The trait can be used on its own. It only defines selector strings.

The following helpers are provided:

* ``getActionSelector()``
* ``getGlobalActionSelector()``
* ``getIndexEntityActionSelector()``
* ``getIndexEntityRowSelector()``
* ``getIndexColumnSelector()``
* ``getIndexHeaderColumnSelector()``
* ``getIndexHeaderRowSelector()``
* ``getFormEntity()``
* ``getEntityFormSelector()``
* ``getFormFieldIdValue()``
* ``getFormFieldSelector()``
* ``getFormFieldLabelSelector()``

.. _`Symfony functional testing workflow`: https://symfony.com/doc/current/testing.html#application-tests
