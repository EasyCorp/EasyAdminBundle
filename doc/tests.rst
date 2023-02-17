Tests
======

As EasyAdmin is based on Symfony, functionally testing the admin pages can leverage the
`Symfony functional testing workflow`_ extending the :code:`WebTestCase` class.

But, as EasyAdmin uses specific defined ways of displaying the data in its Crud pages,
a custom test class is provided : :code:`AbstractCrudTestCase`. The
class is based on traits which defines custom asserts and custom helpers.


1. `Functional Test Case Example`_
2. `Url Generation`_
3. `Asserts`_
4. `Selector Helpers`_


Functional Test Case Example
-------------------------------------------

Suppose you have a `Dashboard`_ named :code:`App\\Controller\\Admin\\AppDashboardController` and
a `Category Crud Controller`_ named :code:`App\\Controller\\Admin\\CategoryCrudController`. Here's an
example of a functional test class for that Controller.

First, your test class need to extend the :code:`AbstractCrudTestCase`.  

.. code-block:: php

    # tests/Admin/Controller/CategoryCrudControllerTest.php
    namespace App\Tests\Admin\Controller;

    use App\Controller\Admin\CategoryCrudController;
    use App\Controller\Admin\AppDashboardController
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
            // no use of security here, up to you to ensure the login in your test in case it's necessary
            $this->client->request("GET", $this->generateIndexUrl());
            static::assertResponseIsSuccessful();
        }
    }


Url Generation
------------------------
Used by the :code:`AbstractCrudTestCase`, :code:`CrudTestUrlGeneration` is an url generation trait which helps to generate the specific of
the EasyAdmin urls.

.. note:: 

    The trait can, of course, be used on its own but in that case, the class that is using it needs either:

    - to define the 2 functions :code:`getControllerFqcn` and :code:`getDashboardFqcn`
    - to add the DashboardFqcn (class name) and ControllerFqcn (class name) as input to the url generation functions

Here is the list of the url generation functions that are all providing url based on provided Dashboard 
& Controller class names:

- :code:`getCrudUrl` : is the main function that allows for a complete generation with all possible options.
- :code:`generateIndexUrl` : generates the url for the index page (based on the Dashboard and Controller defined)
- :code:`generateNewFormUrl` : generates the url for the New form page (based on the Dashboard and Controller defined)
- :code:`generateEditFormUrl` : generates the url for the Edit form page of a specific entity (based on the Dashboard and Controller defined and the entity Id)
- :code:`generateDetailUrl` : generates the url for the Detail page of a specific entity (based on the Dashboard and Controller defined and the entity Id)
- :code:`generateFilterRenderUrl` : generates the url to get the rendering of the filters (based on the Dashboard and Controller defined)

Asserts
------------------------
Used by the `AbstractCrudTestCase`, are 2 traits filled with specific asserts for EasyAdmin web testing:

- :code:`CrudTestIndexAsserts`: providing asserts for the index page of EasyAdmin
- :code:`CrudTestFormAsserts` : providing asserts for the form page of EasyAdmin

.. note:: 

    The trait can, of course, be used on its own but in that case, the class that is using it needs both:

    - a class property :code:`client` : instance of :code:`Symfony\\Bundle\\FrameworkBundle\\KernelBrowser`
    - a class property :code:`entitytManager` : instance of :code:`Doctrine\\ORM\\EntityManagerInterface`
  

CrudTestIndexAsserts
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
As EasyAdmin uses specific layout, the goal of these Asserts is to ease the way you're testing your EasyAdmin backend by providing specific asserts linked to the Index page.

The following asserts are existing:

- :code:`assertIndexFullEntityCount`
- :code:`assertIndexPageEntityCount`
- :code:`assertIndexPagesCount`
- :code:`assertIndexEntityActionExists`
- :code:`assertIndexEntityActionNotExists`
- :code:`assertIndexEntityActionTextSame`
- :code:`assertIndexEntityActionNotTextSame`
- :code:`assertGlobalActionExists`
- :code:`assertGlobalActionNotExists`
- :code:`assertGlobalActionDisplays`
- :code:`assertGlobalActionNotDisplays`
- :code:`assertIndexColumnExists`
- :code:`assertIndexColumnNotExists`
- :code:`assertIndexColumnHeaderContains`
- :code:`assertIndexColumnHeaderNotContains`


CrudTestFormAsserts
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
As EasyAdmin uses specific layout, the goal of these Asserts is to ease the way you're testing your EasyAdmin backend by providing specific asserts linked to the **Form** (New or Edit) page.

The following asserts are existing:

- :code:`assertFormFieldExists`
- :code:`assertFormFieldNotExists`
- :code:`assertFormFieldHasLabel`
- :code:`assertFormFieldNotHasLabel`


Selector Helpers
------------------------
Used by the Asserts to locate elements, the Trait :code:`CrudTestSelectors` is defining a specific amounts of selector helpers linked to the specificities of EasyAdmin layout. 

.. note:: 

    The trait can, of course, be used on its own. It only defines selector strings. 

The following helpers are existing:
 

- :code:`getActionSelector` 
- :code:`getGlobalActionSelector` 
- :code:`getIndexEntityActionSelector` 
- :code:`getIndexEntityRowSelector` 
- :code:`getIndexColumnSelector` 
- :code:`getIndexHeaderColumnSelector` 
- :code:`getIndexHeaderRowSelector` 
- :code:`getFormEntity`
- :code:`getEntityFormSelector`  
- :code:`getFormFieldIdValue` 
- :code:`getFormFieldSelector` 
- :code:`getFormFieldLabelSelector` 


.. _`Symfony functional testing workflow`: https://symfony.com/doc/current/testing.html#application-tests
.. _Dashboard: https://symfony.com/bundles/EasyAdminBundle/4.x/dashboards.html
.. _`Category Crud Controller`: https://symfony.com/bundles/EasyAdminBundle/4.x/crud.html
