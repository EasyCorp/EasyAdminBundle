EasyAdmin
=========

.. raw:: html

    <div class="box box--small box--warning">
        <strong class="title">WARNING:</strong>

        You are browsing the documentation for <strong>EasyAdmin 3.x</strong>,
        which has just been released. Switch to
        <a href="https://symfony.com/doc/2.x/bundles/EasyAdminBundle/index.html">EasyAdmin 2.x docs</a>
        if your application has not been upgraded to EasyAdmin 3 yet.
    </div>

`EasyAdmin`_ creates beautiful administration backends for your Symfony
applications. It's free, fast and fully documented.

If you already used previous EasyAdmin versions, beware that EasyAdmin 3 uses a
brand new architecture and it's incompatible with previous versions. However,
there's a command to :doc:`upgrade from EasyAdmin 2 to EasyAdmin 3 automatically </upgrade>`.

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
    upgrade

Technical Requirements
----------------------

EasyAdmin requires the following:

* PHP 7.2 or higher;
* Symfony 4.4 or higher;
* Doctrine ORM entities (Doctrine ODM is not supported).

Installation
------------

Run the following command to install EasyAdmin in your application:

.. code-block:: terminal

    $ composer require easycorp/easyadmin-bundle

Now you are ready to :doc:`create your first Dashboard </dashboards>`.

.. _`EasyAdmin`: https://github.com/EasyCorp/EasyAdminBundle
