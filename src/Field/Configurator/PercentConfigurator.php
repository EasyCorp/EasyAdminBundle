<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\PercentField;
use Symfony\Component\Form\Extension\Core\DataTransformer\NumberToLocalizedStringTransformer;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class PercentConfigurator implements FieldConfiguratorInterface
{
    
    private $intlFormatter;

    public function __construct(IntlFormatter $intlFormatter)
    {
        $this->intlFormatter = $intlFormatter;
    }

    public function supports(FieldDto $field, EntityDto $entityDto): bool
    {
        return PercentField::class === $field->getFieldFqcn();
    }

    public function configure(FieldDto $field, EntityDto $entityDto, AdminContext $context): void
    {
        $scale = $field->getCustomOption(PercentField::OPTION_NUM_DECIMALS);
        $symbol = $field->getCustomOption(PercentField::OPTION_SYMBOL);
        $isStoredAsFractional = $field->getCustomOption(PercentField::OPTION_STORED_AS_FRACTIONAL);

        $field->setFormTypeOptionIfNotSet('scale', $scale);
        $field->setFormTypeOptionIfNotSet('symbol', $symbol);
        $field->setFormTypeOptionIfNotSet('type', $isStoredAsFractional ? 'fractional' : 'integer');

        if (null === $field->getValue()) {
            return;
        }

        $value = $field->getValue();
        $field->setFormattedValue(sprintf('%s%s', $isStoredAsFractional ? 100 * $value : $value, $symbol));
    }
    
    public function setRoundingMode(int $mode): self
    {
        $validModes = [
            'ROUND_DOWN' => NumberToLocalizedStringTransformer::ROUND_DOWN,
            'ROUND_FLOOR' => NumberToLocalizedStringTransformer::ROUND_FLOOR,
            'ROUND_UP' => NumberToLocalizedStringTransformer::ROUND_UP,
            'ROUND_CEILING' => NumberToLocalizedStringTransformer::ROUND_CEILING,
            'ROUND_HALF_DOWN' => NumberToLocalizedStringTransformer::ROUND_HALF_DOWN,
            'ROUND_HALF_EVEN' => NumberToLocalizedStringTransformer::ROUND_HALF_EVEN,
            'ROUND_HALF_UP' => NumberToLocalizedStringTransformer::ROUND_HALF_UP,
        ];

        if (!\in_array($mode, $validModes, true)) {
            throw new \InvalidArgumentException(sprintf('The argument of the "%s()" method must be the value of any of the following constants from the %s class: %s.', __METHOD__, NumberToLocalizedStringTransformer::class, implode(', ', array_keys($validModes))));
        }

        $this->setCustomOption(self::OPTION_ROUNDING_MODE, $mode);

        return $this;
    }

}
