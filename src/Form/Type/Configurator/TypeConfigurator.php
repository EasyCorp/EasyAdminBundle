<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Type\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Context\ApplicationContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Dto\PropertyDto;
use Symfony\Component\Form\FormConfigInterface;

/**
 * @author Konstantin Grachev <me@grachevko.ru>
 */
// TODO: this can be removed by adding the needed data to the PropertyDto in PropertyBuilder
final class TypeConfigurator implements TypeConfiguratorInterface
{
    private $applicationContextProvider;

    public function __construct(ApplicationContextProvider $applicationContextProvider)
    {
        $this->applicationContextProvider = $applicationContextProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function configure(string $name, array $formFieldOptions, PropertyDto $propertyDto, FormConfigInterface $parentConfig): array
    {
        if (!\array_key_exists('label', $formFieldOptions) && null !== $propertyDto->getLabel()) {
            $formFieldOptions['label'] = $propertyDto->getLabel();
        }

        if (empty($formFieldOptions['translation_domain'])) {
            $formFieldOptions['translation_domain'] = $this->applicationContextProvider->getContext()->getI18n()->getTranslationDomain();
        }

        return $formFieldOptions;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(string $formTypeFqcn, array $formFieldOptions, PropertyDto $propertyDto): bool
    {
        return true;
    }
}
