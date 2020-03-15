<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Type\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormConfigInterface;

/**
 * This configurator is applied to any form field of type 'checkbox' and is used
 * to decide whether the field should be required or not.
 *
 * @author Maxime Steinhausser <maxime.steinhausser@gmail.com>
 */
class CheckboxTypeConfigurator implements TypeConfiguratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function configure(string $name, array $formFieldOptions, FieldDto $fieldDto, FormConfigInterface $parentConfig): array
    {
        // If no value is provided explicitly for the "required" option, assume the checkbox is not required.
        // Otherwise, HTML5 validation will prevent the form from being submitted.
        if (!isset($formFieldOptions['required'])) {
            $formFieldOptions['required'] = false;
        }

        $formFieldOptions['label'] = $fieldDto->getLabel();

        return $formFieldOptions;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(string $formTypeFqcn, array $formFieldOptions, FieldDto $fieldDto): bool
    {
        return CheckboxType::class === $formTypeFqcn;
    }
}
