EasyAdmin
=========

`EasyAdmin`_ creates beautiful administration backends for your Symfony
applications. It's free, fast and fully documented.

If you already used previous EasyAdmin versions, beware that EasyAdmin 3 uses a
brand new architecture and it's incompatible with previous versions. However,
there's a command to :doc:`upgrade from EasyAdmin 2 to EasyAdmin 3 automatically </upgrade>`.

.. admonition:: Screencast
    :class: screencast

    Like video tutorials? Check out the `EasyAdmin Screencast on SymfonyCasts`_.

Table of Contents
-----------------

.. toctree::
    :maxdepth: 1

    dashboards
    crud
    design
    fields
    filters
    actions
    security
    events
    translation
    upgrade

Technical Requirements
----------------------

EasyAdmin requires the following:

* PHP 8.0.2 or higher;
* Symfony 5.4 or higher;
* Doctrine ORM entities (Doctrine ODM is not supported).

Installation
------------

Run the following command to install EasyAdmin in your application:

.. code-block:: terminal

    $ composer require easycorp/easyadmin-bundle

Now you are ready to :doc:`create your first Dashboard </dashboards>`.

.. _`EasyAdmin`: https://github.com/EasyCorp/EasyAdminBundle
.. _`EasyAdmin Screencast on SymfonyCasts`: https://symfonycasts.com/screencast/easyadminbundle
