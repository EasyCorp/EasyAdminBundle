<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Contracts\Translation\TranslatableInterface;

final class PasswordField implements FieldInterface
{
    use FieldTrait;

    public const OPTION_HASH_PASSWORD = 'hashPassword';

    public static function new(string $propertyName, TranslatableInterface|string|bool|null $label = null): self
    {
        return (new self())
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setTemplateName('crud/field/password')
            ->setFormType(PasswordType::class)
            ->addCssClass('field-password');
    }

    public function hashPassword(callable $passwordHasher): self
    {
        $this->setCustomOption(self::OPTION_HASH_PASSWORD, $passwordHasher);

        return $this;
    }
}
