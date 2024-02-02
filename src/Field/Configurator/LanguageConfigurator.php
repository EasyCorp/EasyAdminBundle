<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
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
        $field->setFormTypeOptionIfNotSet('attr.data-ea-widget', 'ea-autocomplete');

        $languageCodeFormat = $field->getCustomOption(LanguageField::OPTION_LANGUAGE_CODE_FORMAT);
        $usesAlpha3Codes = LanguageField::FORMAT_ISO_639_ALPHA3 === $languageCodeFormat;

        if (\in_array($context->getCrud()->getCurrentPage(), [Crud::PAGE_EDIT, Crud::PAGE_NEW], true)) {
            $field->setFormTypeOption('choices', $this->generateFormTypeChoices(
                $usesAlpha3Codes,
                $field->getCustomOption(LanguageField::OPTION_LANGUAGE_CODES_TO_KEEP),
                $field->getCustomOption(LanguageField::OPTION_LANGUAGE_CODES_TO_REMOVE))
            );
            $field->setFormTypeOption('choice_loader', null);
            // language names passed to the form are already translated, so don't translate them again in the template
            $field->setFormTypeOptionIfNotSet('choice_translation_domain', false);
        }

        if (null === $languageCode = $field->getValue()) {
            return;
        }

        $languageName = $this->getLanguageName($languageCode, $usesAlpha3Codes);
        if (null === $languageName) {
            throw new \InvalidArgumentException(sprintf('The "%s" value used as the language code of the "%s" field is not a valid ICU language code.', $languageCode, $field->getProperty()));
        }

        $field->setFormattedValue($languageName);
    }

    private function getLanguageName(string $languageCode, bool $usesAlpha3Codes): ?string
    {
        try {
            return $usesAlpha3Codes ? Languages::getAlpha3Name($languageCode) : Languages::getName($languageCode);
        } catch (MissingResourceException) {
            return null;
        }
    }

    private function generateFormTypeChoices(bool $usesAlpha3Codes, ?array $languageCodesToKeep, ?array $languageCodesToRemove): array
    {
        $choices = [];

        $languages = $usesAlpha3Codes ? Languages::getAlpha3Names() : Languages::getNames();
        foreach ($languages as $languageCode => $languageName) {
            if (null !== $languageCodesToKeep && !\in_array($languageCode, $languageCodesToKeep, true)) {
                continue;
            }

            if (null !== $languageCodesToRemove && \in_array($languageCode, $languageCodesToRemove, true)) {
                continue;
            }

            $choices[$languageName] = $languageCode;
        }

        return $choices;
    }
}
