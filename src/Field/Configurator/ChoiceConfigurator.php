<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class ChoiceConfigurator implements FieldConfiguratorInterface
{
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function supports(FieldDto $field, EntityDto $entityDto): bool
    {
        return ChoiceField::class === $field->getFieldFqcn();
    }

    public function configure(FieldDto $field, EntityDto $entityDto, AdminContext $context): void
    {
        $choices = $field->getCustomOption(ChoiceField::OPTION_CHOICES);
        if (empty($choices)) {
            throw new \InvalidArgumentException(sprintf('The "%s" select field must define its possible choices using the setChoices() method.', $field->getProperty()));
        }

        $translatedChoices = [];
        $translationParameters = $context->getI18n()->getTranslationParameters();
        foreach ($choices as $choiceLabel => $choiceValue) {
            $translatedChoiceLabel = $this->translator->trans((string) $choiceLabel, $translationParameters);
            $translatedChoices[$translatedChoiceLabel] = $choiceValue;
        }
        $field->setFormTypeOptionIfNotSet('choices', $translatedChoices);

        if (null !== $value = $field->getValue()) {
            // needed to be compatible with fields that allow selecting multiple values
            $selectedChoices = [];
            $flippedChoices = array_flip($choices);
            // $value is a scalar for single selections and an array for multiple selections
            foreach (array_values((array) $value) as $selectedValue) {
                if (null !== $selectedChoice = $flippedChoices[$selectedValue] ?? null) {
                    $selectedChoices[] = $this->translator->trans($selectedChoice, $translationParameters);
                }
            }

            $field->setFormattedValue(implode(', ', $selectedChoices));
        }

        $field->setFormTypeOptionIfNotSet('multiple', $field->getCustomOption(ChoiceField::OPTION_ALLOW_MULTIPLE_CHOICES));
        $field->setFormTypeOptionIfNotSet('expanded', $field->getCustomOption(ChoiceField::OPTION_RENDER_EXPANDED));

        if (true === $field->getCustomOption(ChoiceField::OPTION_AUTOCOMPLETE)) {
            $field->setFormTypeOptionIfNotSet('attr.data-widget', 'select2');
        }
    }
}
