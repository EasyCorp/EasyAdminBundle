<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class IntegerConfigurator implements FieldConfiguratorInterface
{
    public function supports(FieldDto $field, EntityDto $entityDto): bool
    {
        return IntegerField::class === $field->getFieldFqcn();
    }

    public function configure(FieldDto $field, EntityDto $entityDto, AdminContext $context): void
    {
        if (null === $value = $field->getValue()) {
            return;
        }

        if (null !== $numberFormat = $field->getCustomOption(NumberField::OPTION_NUMBER_FORMAT)) {
            $field->setFormattedValue(sprintf($numberFormat, $value));
        } elseif (null !== $numberFormat = $context->getCrud()->getNumberFormat()) {
            $field->setFormattedValue(sprintf($numberFormat, $value));
        }
    }
}
