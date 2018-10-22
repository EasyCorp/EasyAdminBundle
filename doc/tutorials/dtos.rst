Using DTOs in EasyAdmin
=======================

DTOs (Data Transfer Object) are considered a good practice by many developers as a way to
decouple your database data (the entities) from your domain logic.

This is especially true when using the Symfony Form component, because
it updates the entity's data and therefore can lead to an invalid state.
As invalid states can be the source of inconsistencies, database corruption,
if one single ``$entityManager->flush()`` is forgotten in the code, EasyAdmin
provides a simple way to configure DTOs with Symfony forms.

Introduction to DTOs
--------------------

Let's consider a ``User`` entity:

.. code-block:: php

    /**
     * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
     */
    class User implements UserInterface
    {
        /**
         * @ORM\Id()
         * @ORM\GeneratedValue()
         * @ORM\Column(type="integer")
         */
        private $id;

        /**
         * @ORM\Column(type="string", length=180, unique=true)
         */
        private $email;

        /**
         * @ORM\Column(type="json")
         */
        private $roles = [];

        /**
         * @var string The hashed password
         * @ORM\Column(type="string")
         */
        private $password;

        // ...
    }

As Doctrine does not use any getter nor setter to hydrate our objects, we do not
need any.

If we use DTOs, our entity will be populated by the DTO itself when submitting
the form, so all we need are entrypoint methods in our Entity for this.

We call these **static constructors** (or sometimes "named constructors") and
**mutators**.

Here is an example of such methods for our ``User`` entity:

.. code-block:: php

    class User implements UserInterface
    {
        // ...

        public static function createFromAdmin(NewUserAdminDTO $dto): self
        {
            $obj = new self();

            $obj->username = $dto->getUsername();
            // ...

            return $obj;
        }

        public function updateFromAdmin(ExistingUserAdminDTO $dto): void
        {
            // ...
            $this->username = $dto->getUsername();
        }
    }

Here, we have a nice example of a **static constructor** and a **mutator**,
both using an object called a DTO.

Create your first DTO
---------------------

The goal of the DTO is to replace the Entity in the form. This way, we will
only manipulate a plain old PHP object (POPO) that will carry some data.
This plain object is the actual DTO. We will use it to transfer its data
into our entity, data that will come from the Form submission, and that can
be validated with the Symfony Validator if it is enabled.

Here is an example of the ``new`` DTO:

.. code-block:: php

    namespace App\Form\DTO;

    class NewUserAdminDTO
    {
        private $email;
        private $plainPassword;

        public function getEmail(): ?string
        {
            return $this->email;
        }

        public function setEmail(?string $email): void
        {
            $this->email = $email;
        }

        public function getPlainPassword(): ?string
        {
            return $this->plainPassword;
        }

        public function setPlainPassword(?string $plainPassword): void
        {
            $this->plainPassword = $plainPassword;
        }
    }

This DTO is here to represent the data that will be sent to a potential
"new user" form.

We can have a similar DTO for a "update user" form:

.. code-block:: php

    namespace App\Form\DTO;

    class ExistingUserAdminDTO
    {
        private $email;
        private $resetPassword;

        public static function fromUser(User $user): self
        {
            $new = new self();

            $new->email = $user->getEmail();

            return $new;
        }

        public function getEmail(): ?string
        {
            return $this->email;
        }

        public function setEmail(?string $email): void
        {
            $this->email = $email;
        }

        public function getResetPassword(): ?bool
        {
            return $this->resetPassword;
        }

        public function setResetPassword(?bool $resetPassword)
        {
            $this->resetPassword = $resetPassword;
        }
    }

As you can see here, we even have a **static constructor** in our DTO. Of
course: when editing a User, we need default data! That's what this constructor
is for.

Configuring EasyAdmin to use our DTOs
-------------------------------------

EasyAdmin provides automatic setting up for DTOs with a few configuration
options.

According to the examples above, here are the fields you should add to tell
EasyAdmin to use your DTOs:

.. code-block:: yaml

    easy_admin:
        entities:
            User:
                class: App\Entity\User

                new:
                    dto_class: App\Form\DTO\NewUserAdminDTO
                    dto_entity_callable: createFromAdmin
                    # Default DTO factory is the native constructor, so we don't specify it here.

                edit:
                    dto_class: App\Form\DTO\ExistingUserAdminDTO
                    dto_factory: fromUser
                    dto_entity_callable: updateFromAdmin

                    # You define fields as the DTO fields instead of the Entity one.
                    fields:
                        - email
                        - property: resetPassword
                          type: checkbox

And *voilà*! Nothing more to do, EasyAdmin will use your configuration to create
your DTOs in the right situation, and create or update your entities properly.

DTO configuration options
-------------------------

* ``dto_class``: This is the first thing you have to define if you want to use
  DTOs. It will tell EasyAdmin to separate the DTO (that will be injected in the
  form) and the Entity (that will be used for persist & flush calls on the ORM).
* ``dto_factory``: This option can be of three types:
  * ``null`` (default) will use the native constructor, leading to code like
    ``$dto = new $dtoClass()``. You could also explicitly set ``__construct`` as value,
    which leads to the exact same behavior but is more explicit.
  * The **static method name** that will be used to create the DTO, like
    ``$dtoClass:$dtoFactory()``.
  * A **static factory** from another class like ``'MyDTOFactory::createDTO'``.
  * The **service name** of a factory registered as a service (see below an example of
    object factory). It must implement ``EasyCorp\Bundle\EasyAdminBundle\Form\DTO\DTOFactoryInterface``
    and be tagged ``easyadmin.dto_factory_storage``.
* ``dto_entity_callable``: This is the **callable** that will be used by EasyAdmin
  when the form is **submitted and valid**, to put DTO data into the entity.
  It can also be of different types:
  * A **service name** that will be retrieved from the container and executed like this:
    ``$myService->updateEntity($dto, $entity, $action);`` (see below for a service callable example).
    The service class must implement ``EasyCorp\Bundle\EasyAdminBundle\Form\DTO\DTOEntityCallable``
    and be tagged ``easyadmin.dto_entity_callable``, for proper dependency injection.
  * A **static method** that will execute a statement similar to ``$class::$method($dto, $entity);``.
    It can even be a static method from the entity itself, if you need **private** access to
    certain properties or methods, or if you want to customize Entity creation based on the DTO.
  * A simple ``method name`` in the entity class. This will execute a statement like
    ``$entity->$method($dto);``.
  ⚠️ **If the callable returns anything**, it will **replace** the previously created/retrieved
  ``$entity``. This is done on purpose, in case you want to customize entity creation in the
  ``new`` action, or in case you clone or create a new object in ``edit`` action.

.. best-practice::

    For ``dto_factory``, we recommend using a **static constructor in the DTO** itself.
    This way, the DTO can store private properties and encapsulate them with getters, making
    it a value-object, which is really interesting to avoid it being updated from the userland.
    Then, a DTO can be always valid (as it's validated by the Form), therefore safe for your entities.

.. best-practice::

    For ``dto_entity_callable``, we recommend using an **entity method** such as ``$entity->$method($dto)``.
    This helps the entity update itself and **remove all setters**. The only way an entity can be updated
    then becomes the use of a DTO, which is a good practice.

Create a custom DTO factory
---------------------------

Thanks to the ``EasyCorp\Bundle\EasyAdminBundle\Form\DTO\DTOFactoryInterface``, you can
create services that will create your DTOs.

Here is an example of such factory:

.. code-block:: php

    <?php

    use EasyCorp\Bundle\EasyAdminBundle\Form\DTO\DTOFactoryInterface;

    class CustomObjectFactory implements DTOFactoryInterface
    {
        public function createDTO(string $class, string $view, $defaultData = null)
        {
            // Your logic to create the DTO.
        }
    }

Used with a configuration similar to this:

.. code-block:: yaml

    easy_admin:
        entities:
            User:
                class: App\Entity\User

                edit:
                    dto_class: App\Form\DTO\ExistingUserAdminDTO

                    # Old-fashioned service name
                    dto_factory: app.dto_factory.custom_factory

                    # Symfony 3.3+: class name as service id
                    dto_factory: App\Form\DTOFactory\CustomObjectFactory

Create a custom DTO-to-Entity callable
--------------------------------------

As the ``dto_entity_callable`` can accept different types of values, here are
examples that you might get inspiration from for your projects:

.. code-block:: yaml

    easy_admin:
        entities:
            User:
                class: App\Entity\User

                new:
                    # Will execute $entity->updateFromDTO($dto)
                    dto_entity_callable: updateFromDTO

                    # Will execute static method Any\Other\Class::createFromDTO($dto)
                    dto_entity_callable: Any\Other\Class::createFromDTO

                    # Of course, you can use the entity one:
                    # Will execute static method App\Entity\User::createFromDTO($dto)
                    dto_entity_callable: App\Entity\User::createFromDTO

                    # Container-based callables:
                    # (The service must be tagged "easyadmin.dto_entity_callable" or implement DTOEntityCallable)

                    dto_entity_callable: App\Form\MyCallable

                    # For plain-old services that do not use the class name as service id
                    dto_entity_callable: my_service

Here is an example of a working config & callable:

.. code-block:: yaml

    easy_admin:
        entities:
            User:
                class: App\Entity\User

                edit:
                    dto_class: App\Form\DTO\ExistingUserAdminDTO
                    dto_entity_callable: App\Form\DTO\ExistingUserDTOCallable

.. code-block:: php

    <?php

    namespace App\Form\DTO;

    use App\Entity\User;
    use App\Form\DTO\ExistingUserAdminDTO;
    use EasyCorp\Bundle\EasyAdminBundle\Form\DTO\DTOEntityCallable;

    class ExistingUserDTOCallable implements DTOEntityCallable
    {
        /**
         * {@inheritdoc}
         */
        public function updateEntity($dto, $entity, string $action)
        {
            if ('new' === $action) {
                $this->new($dto, $entity);
            } elseif ('edit' === $action) {
                $this->edit($dto, $entity);
            }
        }

        /**
         * Using a private method like this forces DTO and entity types.
         * This is helpful for self-documenting DTO-related code and for debugging.
         */
        private function edit(ExistingUserAdminDTO $DTO, User $user)
        {
            // ... your logic
        }
    }
