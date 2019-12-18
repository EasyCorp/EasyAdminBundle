<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Type\Configurator;

use Doctrine\ORM\Mapping\ClassMetadata;
use EasyCorp\Bundle\EasyAdminBundle\Dto\PropertyDto;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\EasyAdminAutocompleteType;
use Symfony\Component\Form\FormConfigInterface;

/**
 * This configurator is applied to any form field of type 'easyadmin_autocomplete'
 * and is used to configure the class of the autocompleted entity.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
class AutocompleteTypeConfigurator implements TypeConfiguratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function configure(string $name, array $formFieldOptions, PropertyDto $propertyDto, FormConfigInterface $parentConfig): array
    {
        // TODO: add targetEntity and associationType to PropertyDto

        // by default, guess the mandatory 'class' option from the Doctrine metadata
        if (!isset($formFieldOptions['class']) && isset($propertyDto['targetEntity'])) {
            $formFieldOptions['class'] = $propertyDto['targetEntity'];
        }

        // by default, allow to autocomplete multiple values for OneToMany and ManyToMany associations
        if (!isset($formFieldOptions['multiple']) && isset($propertyDto['associationType']) && $propertyDto['associationType'] & ClassMetadata::TO_MANY) {
            $formFieldOptions['multiple'] = true;
        }

        if (null !== $propertyDto->getLabel() && !isset($formFieldOptions['label'])) {
            $formFieldOptions['label'] = $propertyDto->getLabel();
        }

        return $formFieldOptions;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(string $formTypeFqcn, array $formFieldOptions, PropertyDto $propertyDto): bool
    {
        return EasyAdminAutocompleteType::class === $formTypeFqcn;
    }
}
