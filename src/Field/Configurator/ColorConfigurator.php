<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\ColorField;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class ColorConfigurator implements FieldConfiguratorInterface
{
    public function supports(FieldDto $field, EntityDto $entityDto): bool
    {
        return ColorField::class === $field->getFieldFqcn();
    }

    public function configure(FieldDto $field, EntityDto $entityDto, AdminContext $context): void
    {
        // the value is rendered inside a CSS `style` attribute; only accept the
        // hex notation produced by `<input type="color">` (and its shorthand
        // variants) to prevent CSS injection via characters like `;`, `:`, `(`.
        $value = $field->getValue();
        if (null !== $value && (!\is_string($value) || 1 !== preg_match('/^#[0-9a-fA-F]{3,8}$/', $value))) {
            $field->setValue(null);
            $field->setFormattedValue(null);
        }
    }
}
