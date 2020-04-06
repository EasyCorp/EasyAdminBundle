Integrating FOSUserBundle to Manage Users
=========================================

`FOSUserBundle`_ is a popular Symfony bundle which simplifies the management
of users in Symfony applications. This article explains how to better integrate
it with EasyAdmin to manage users' information. The article assumes that you
have installed FOSUserBundle and have created a user entity as explained in
`its documentation`_.

Creating New Users
------------------

FOSUserBundle defines a `user manager`_ to handle all operations on user
instances, such as creating and editing users. This manager, which is accessed
through the ``fos_user.user_manager`` service, makes the bundle "agnostic" to
where the users are stored and it's a good practice to use it.

Before using this manager, read :doc:`../tutorials/custom-actions` if you
haven't done it already so you can modify the behavior of the new action. Then,
override the ``createNewUserEntity()`` and ``persistUserEntity()`` methods to
override the way users are created and persisted:

.. code-block:: php

    // src/Controller/AdminController.php
    namespace App\Controller;

    use EasyCorp\Bundle\EasyAdminBundle\Controller\EasyAdminController;

    class AdminController extends EasyAdminController
    {
        public function createNewUserEntity()
        {
            return $this->get('fos_user.user_manager')->createUser();
        }

        public function persistUserEntity($user)
        {
            $this->get('fos_user.user_manager')->updateUser($user, false);
            parent::persistEntity($user);
        }
    }

The ``false`` value of the second argument of ``updateUser()`` tells
FOSUserBundle to not save the changes (to not flush the UnitOfWork) at that
moment and to let Doctrine take care of saving those changes when needed.

.. note::

    If your user entity is not called ``User``, you need to change the above
    method names. For example, if the entity is called ``Customers``, the
    methods to define are ``createNewCustomersEntity()`` and
    ``persistCustomersEntity()``.

Editing User Information
------------------------

FOSUserBundle provides a custom ``User`` entity with some predefined properties,
such as ``email``, ``enabled`` and ``lastLogin``. You can manage these
properties in the same way you manage any property of any other entity:

.. code-block:: yaml

    # config/packages/easy_admin.yaml
    easy_admin:
        entities:
            User:
                class: App\Entity\User
                form:
                    fields:
                        - username
                        - email
                        - enabled
                        - lastLogin
                        # if administrators are allowed to edit users' passwords and roles, add this:
                        - { property: 'plainPassword', type: 'text', type_options: { required: false } }
                        - { property: 'roles', type: 'choice', type_options: { multiple: true, choices: { 'ROLE_USER': 'ROLE_USER', 'ROLE_ADMIN': 'ROLE_ADMIN' } } }

However, it's recommended to save changes using FOSUserBundle's user manager.
Therefore, open your AdminController and add the following method:

.. code-block:: php

    // src/Controller/AdminController.php
    class AdminController extends BaseAdminController
    {
        // ...

        public function updateUserEntity($user)
        {
            $this->get('fos_user.user_manager')->updateUser($user, false);
            parent::updateEntity($user);
        }
    }

.. note::

    If your user entity is not called ``User``, you need to change the above
    method name. For example, if the entity is called ``Customers``, the method
    to define is ``updateCustomersEntity()``.

Using Different Validation when Creating or Editing the User
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

A common need when managing users is to apply different validation rules when
creating or editing them. In the following example, the form applies different
validation groups for each action and the password is mandatory only when the
user is created:

.. code-block:: yaml

    # config/packages/easy_admin.yaml
    easy_admin:
        entities:
            User:
                class: App\Entity\User
                edit:
                    fields:
                        # ...
                        - { property: 'plainPassword', type_options: { required: false} }
                        # ...
                    form_options: { validation_groups: ['Profile'] }
                new:
                    fields:
                        # ...
                        - { property: 'plainPassword', type_options: { required: true} }
                        # ...
                    form_options: { validation_groups: ['Registration'] }

.. _`FOSUserBundle`: https://github.com/FriendsOfSymfony/FOSUserBundle
.. _`its documentation`: https://symfony.com/doc/current/bundles/FOSUserBundle/index.html
.. _`user manager`: https://symfony.com/doc/current/bundles/FOSUserBundle/user_manager.html
