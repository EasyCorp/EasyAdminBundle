<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\LanguageField;
use Symfony\Component\Intl\Exception\MissingResourceException;
use Symfony\Component\Intl\Languages;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class LanguageConfigurator implements FieldConfiguratorInterface
{
    public function supports(FieldDto $field, EntityDto $entityDto): bool
    {
        return LanguageField::class === $field->getFieldFqcn();
    }

    public function configure(FieldDto $field, EntityDto $entityDto, AdminContext $context): void
    {
        $field->setFormTypeOptionIfNotSet('attr.data-widget', 'select2');

        if (null === $languageCode = $field->getValue()) {
            return;
        }

        $languageName = $this->getLanguageName($languageCode);
        if (null === $languageName) {
            throw new \InvalidArgumentException(sprintf('The "%s" value used as the language code of the "%s" field is not a valid ICU language code.', $languageCode, $field->getProperty()));
        }

        $field->setFormattedValue($languageName);
    }

    private function getLanguageName(string $languageCode): ?string
    {
        try {
            return Languages::getName($languageCode);
        } catch (MissingResourceException $e) {
            return null;
        }
    }
}
