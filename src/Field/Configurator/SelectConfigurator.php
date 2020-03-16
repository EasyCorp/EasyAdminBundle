<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\SelectField;
use Symfony\Contracts\Translation\TranslatorInterface;

final class SelectConfigurator implements FieldConfiguratorInterface
{
    private $adminContextProvider;
    private $translator;

    public function __construct(AdminContextProvider $adminContextProvider, TranslatorInterface $translator)
    {
        $this->adminContextProvider = $adminContextProvider;
        $this->translator = $translator;
    }

    public function supports(FieldInterface $field, EntityDto $entityDto): bool
    {
        return $field instanceof SelectField;
    }

    public function configure(FieldInterface $field, EntityDto $entityDto, string $action): void
    {
        $choices = $field->getCustomOption(SelectField::OPTION_CHOICES);
        if (empty($choices)) {
            throw new \InvalidArgumentException(sprintf('The "%s" select field must define its possible choices using the setChoices() method.', $field->getProperty()));
        }

        $translatedChoices = [];
        $translationParameters = $this->adminContextProvider->getContext()->getI18n()->getTranslationParameters();
        foreach ($choices as $key => $value) {
            $translatedKey = $this->translator->trans($key, $translationParameters);
            $translatedChoices[$translatedKey] = $value;
        }
        $field->setFormTypeOptionIfNotSet('choices', $translatedChoices);

        if (null !== $value = $field->getValue()) {
            $selectedChoice = array_flip($choices)[$value];
            $field->setFormattedValue($this->translator->trans($selectedChoice, $translationParameters));
        }

        if (true === $field->getCustomOption(SelectField::OPTION_AUTOCOMPLETE)) {
            $field->setFormTypeOptionIfNotSet('attr.data-widget', 'select2');
        }
    }
}
