<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\LanguageField;
use Symfony\Component\Intl\Exception\MissingResourceException;
use Symfony\Component\Intl\Languages;

final class LanguageConfigurator implements FieldConfiguratorInterface
{
    public function supports(FieldInterface $field, EntityDto $entityDto): bool
    {
        return $field instanceof LanguageField;
    }

    public function configure(string $action, FieldInterface $field, EntityDto $entityDto): void
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
        // Compatibility with Symfony versions before 4.3
        if (!class_exists(Languages::class)) {
            return Intl::getLanguageBundle()->getLanguageName($languageCode) ?? null;
        }

        try {
            return Languages::getName($languageCode);
        } catch (MissingResourceException $e) {
            return null;
        }
    }
}
