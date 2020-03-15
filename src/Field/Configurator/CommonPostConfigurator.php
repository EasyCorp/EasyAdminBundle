<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;

final class CommonPostConfigurator implements FieldConfiguratorInterface
{
    public function supports(FieldInterface $field, EntityDto $entityDto): bool
    {
        // this configurator applies to all kinds of properties
        return true;
    }

    public function configure(string $action, FieldInterface $field, EntityDto $entityDto): void
    {
        $formattedValue = $this->buildFormattedValueOption($field->getFormattedValue(), $field, $entityDto);
        $field->setFormattedValue($formattedValue);
    }

    private function buildFormattedValueOption($value, FieldInterface $field, EntityDto $entityDto)
    {
        if (null === $callable = $field->getFormatValueCallable()) {
            return $value;
        }

        return \call_user_func($callable, $value, $entityDto->getInstance());
    }
}
