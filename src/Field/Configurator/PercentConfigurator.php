<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\PercentField;
use Symfony\Component\Form\ChoiceList\ChoiceList;

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
        $scale = $field->getCustomOption(PercentField::OPTION_NUM_DECIMALS);
        $roundingMode = $field->getCustomOption(PercentField::OPTION_ROUNDING_MODE);
        $symbol = $field->getCustomOption(PercentField::OPTION_SYMBOL);
        $isStoredAsFractional = $field->getCustomOption(PercentField::OPTION_STORED_AS_FRACTIONAL);

        $field->setFormTypeOptionIfNotSet('scale', $scale);
        $field->setFormTypeOptionIfNotSet('symbol', $symbol);
        $field->setFormTypeOptionIfNotSet('type', $isStoredAsFractional ? 'fractional' : 'integer');

        // PercentType added 'rounding_mode' option in Symfony Form 5.1 (this option
        // can't be detected directly; instead, detect ChoiceList class, which was added in 5.1)
        if (class_exists(ChoiceList::class)) {
            $field->setFormTypeOptionIfNotSet('rounding_mode', $roundingMode);
        }

        if (null === $field->getValue()) {
            return;
        }

        $value = $field->getValue();
        $field->setFormattedValue(sprintf('%s%s', $isStoredAsFractional ? 100 * $value : $value, $symbol));
    }
}
