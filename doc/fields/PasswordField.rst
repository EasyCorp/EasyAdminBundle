PasswordField
=============

This field is used to represent a user password in forms. On index and detail 
pages, the password value is hidden and displayed as a sequence of bullets for 
security purposes.

Basic Information
-----------------

* **PHP Class**: ``EasyCorp\Bundle\EasyAdminBundle\Field\PasswordField``
* **Doctrine DBAL Type**: ``string``
* **Symfony Form Type**: ``Symfony\Component\Form\Extension\Core\Type\PasswordType``
* **Rendered as**: ``<input type="password">``

Usage
-----

Basic usage::

    use EasyCorp\Bundle\EasyAdminBundle\Field\PasswordField;

    public function configureFields(string $pageName): iterable
    {
        return [
            // ...
            PasswordField::new('password', 'User password'),
        ];
    }

Password Hashing
----------------

In most modern applications, plain text passwords should never be stored in the database.
You can use the ``hashPassword()`` method to define a callable that processes the plain 
password submitted in the form before it is saved into the entity::

    use EasyCorp\Bundle\EasyAdminBundle\Field\PasswordField;
    use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

    class UserCrudController extends AbstractCrudController
    {
        public function __construct(
            private UserPasswordHasherInterface $userPasswordHasher
        ) {}

        public function configureFields(string $pageName): iterable
        {
            $hashPassword = function ($plainPassword) {
                // you can get the user entity from the current context or create a dummy one
                // based on your Symfony configuration
                return $this->userPasswordHasher->hashPassword($this->getUser(), $plainPassword);
            };

            return [
                PasswordField::new('password')
                    ->hashPassword($hashPassword),
            ];
        }
    }

Alternatively, you could use Doctrine Entity Listeners to hash the password right before the entity is strictly persisted. Either way works natively with this field.
