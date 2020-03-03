<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Property\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Context\ApplicationContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfigInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Property\SelectProperty;
use Symfony\Contracts\Translation\TranslatorInterface;

final class SelectConfigurator implements PropertyConfiguratorInterface
{
    private $applicationContextProvider;
    private $translator;

    public function __construct(ApplicationContextProvider $applicationContextProvider, TranslatorInterface $translator)
    {
        $this->applicationContextProvider = $applicationContextProvider;
        $this->translator = $translator;
    }

    public function supports(PropertyConfigInterface $propertyConfig, EntityDto $entityDto): bool
    {
        return $propertyConfig instanceof SelectProperty;
    }

    public function configure(string $action, PropertyConfigInterface $propertyConfig, EntityDto $entityDto): void
    {
        $choices = $propertyConfig->getCustomOption(SelectProperty::OPTION_CHOICES);
        if (empty($choices)) {
            throw new \InvalidArgumentException(sprintf('The "%s" select property must define its possible choices using the setChoices() method.', $propertyConfig->getName()));
        }

        $translatedChoices = [];
        $translationParams = $this->applicationContextProvider->getContext()->getI18n()->getTranslationParams();
        foreach ($choices as $key => $value) {
            $translatedKey = $this->translator->trans($key, $translationParams);
            $translatedChoices[$translatedKey] = $value;
        }
        $propertyConfig->setFormTypeOptionIfNotSet('choices', $translatedChoices);

        if (null !== $value = $propertyConfig->getValue()) {
            $selectedChoice = array_flip($choices)[$value];
            $propertyConfig->setFormattedValue($this->translator->trans($selectedChoice, $translationParams));
        }
    }
}
