<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Type\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormConfigInterface;

/**
 * This configurator is applied to any form field of type 'textarea'.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class TextareaTypeConfigurator implements TypeConfiguratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function configure(string $name, array $formFieldOptions, FieldDto $fieldDto, FormConfigInterface $parentConfig): array
    {
        // if <textarea> doesn't define its rows, use a default number to make it look good
        if (!isset($formFieldOptions['attr']['rows'])) {
            $formFieldOptions['attr']['rows'] = 5;
        }

        return $formFieldOptions;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(string $formTypeFqcn, array $formFieldOptions, FieldDto $fieldDto): bool
    {
        return TextareaType::class === $formTypeFqcn;
    }
}
