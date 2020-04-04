<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\PercentField;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class PercentConfigurator implements FieldConfiguratorInterface
{
    public function supports(FieldDto $field, EntityDto $entityDto): bool
    {
        return PercentField::class === $field->getFieldFqcn();
    }

    public function configure(FieldDto $field, EntityDto $entityDto, AdminContext $context): void
    {
        if (null === $field->getValue()) {
            return;
        }

        $scale = $field->getCustomOption(PercentField::OPTION_NUM_DECIMALS);
        $symbol = $field->getCustomOption(PercentField::OPTION_SYMBOL);
        $isStoredAsFractional = $field->getCustomOption(PercentField::OPTION_STORED_AS_FRACTIONAL);
        $value = $field->getValue();

        $field->setFormattedValue(sprintf('%s%s', $isStoredAsFractional ? 100 * $value : $value, $symbol));

        $field->setFormTypeOptionIfNotSet('scale', $scale);
        $field->setFormTypeOptionIfNotSet('symbol', $symbol);
        $field->setFormTypeOptionIfNotSet('type', $isStoredAsFractional ? 'fractional' : 'integer');
    }
}
