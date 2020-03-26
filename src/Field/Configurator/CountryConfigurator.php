<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\CountryField;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Intl\Exception\MissingResourceException;

final class CountryConfigurator implements FieldConfiguratorInterface
{
    public function supports(FieldDto $field, EntityDto $entityDto): bool
    {
        return CountryField::class === $field->getFieldFqcn();
    }

    public function configure(FieldDto $field, EntityDto $entityDto, AdminContext $context): void
    {
        $field->setFormTypeOptionIfNotSet('attr.data-widget', 'select2');

        $formattedValue = $this->getCountryName($field->getValue());
        $field->setFormattedValue($formattedValue);

        if (null === $field->getTextAlign() && false === $field->getCustomOption(CountryField::OPTION_SHOW_NAME)) {
            $field->setTextAlign('center');
        }

        if (null === $formattedValue) {
            $field->setTemplateName('label/null');
        }
    }

    private function getCountryName(?string $countryCode): ?string
    {
        if (null === $countryCode) {
            return null;
        }

        // Compatibility with Symfony versions before 4.3
        if (!class_exists(Countries::class)) {
            return Intl::getRegionBundle()->getCountryName($countryCode);
        }

        try {
            return Countries::getName($countryCode);
        } catch (MissingResourceException $e) {
            return null;
        }
    }
}
