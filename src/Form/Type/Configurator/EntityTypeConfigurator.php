<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Type\Configurator;

use Doctrine\ORM\Mapping\ClassMetadata;
use EasyCorp\Bundle\EasyAdminBundle\Dto\PropertyDto;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormConfigInterface;

/**
 * This configurator is applied to any form field of type 'association' and is
 * used to configure lots of their features (for example whether we should use
 * a JavaScript widget to display their contents).
 *
 * @author Maxime Steinhausser <maxime.steinhausser@gmail.com>
 */
class EntityTypeConfigurator implements TypeConfiguratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function configure(string $name, array $formFieldOptions, PropertyDto $propertyDto, FormConfigInterface $parentConfig): array
    {
        if (!isset($formFieldOptions['multiple']) && $propertyDto['associationType'] & ClassMetadata::TO_MANY) {
            $formFieldOptions['multiple'] = true;
        }

        // Supported associations are displayed using advanced JavaScript widgets
        $formFieldOptions['attr']['data-widget'] = 'select2';

        // Configure "placeholder" option for entity fields
        if (($propertyDto['associationType'] & ClassMetadata::TO_ONE)
            && !isset($formFieldOptions['placeholder'])
            && isset($formFieldOptions['required']) && false === $formFieldOptions['required']
        ) {
            $formFieldOptions['placeholder'] = 'label.form.empty_value';
        }

        return $formFieldOptions;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(string $formTypeFqcn, array $formFieldOptions, PropertyDto $propertyDto): bool
    {
        // TODO: add suport for this
        return false;
        $isEntityType = \in_array($type, ['entity', EntityType::class], true);

        return $isEntityType && 'association' === $metadata['dataType'];
    }
}
