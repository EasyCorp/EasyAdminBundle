<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Type\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use Symfony\Component\Form\FormConfigInterface;

/**
 * This is the interface implemented by all the form type configurations. They
 * allow to add specific configuration options for each form type, no matter if
 * they are built-in Symfony types, custom types or types provided by third-party
 * bundles.
 *
 * @author Maxime Steinhausser <maxime.steinhausser@gmail.com>
 */
interface TypeConfiguratorInterface
{
    /**
     * Configure the options for this type.
     *
     * @param string              $name                     The form field name
     * @param array               $formFieldFormFieldOtions The configured field options provided by Symfony Form component
     * @param array               $metadata                 The PropertyDto object related to this field
     * @param FormConfigInterface $parentConfig             The parent form configuration
     *
     * @return array The array with the updated form field options
     */
    public function configure(string $name, array $formFieldOptions, FieldDto $fieldDto, FormConfigInterface $parentConfig): array;

    /**
     * Returns true if the type option configurator supports this field.
     *
     * @param string $formTypeFqcn     The FQCN of the form type
     * @param array  $formFieldOptions The configured field options provided by Symfony Form component
     * @param array  $fieldDto      The PropertyDto object related to this field
     */
    public function supports(string $formTypeFqcn, array $formFieldOptions, FieldDto $fieldDto): bool;
}
