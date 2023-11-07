<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class EmailField extends AbstractField
{
    public static function new(string $propertyName, TranslatableInterface|string|false|null $label = null): FieldInterface
    {
        return parent::new($propertyName, $label)
            ->setTemplateName('crud/field/email')
            ->setFormType(EmailType::class)
            ->addCssClass('field-email')
            ->setDefaultColumns('col-md-6 col-xxl-5');
    }
}
