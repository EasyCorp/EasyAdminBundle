<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\ChoiceFilterType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\Configurator\TypeConfiguratorInterface;
use Symfony\Component\Form\FormConfigInterface;

/**
 * TODO: delete this class.
 *
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
class ChoiceFilterTypeConfigurator implements TypeConfiguratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function configure(string $name, array $formFieldOptions, FieldDto $fieldDto, FormConfigInterface $parentConfig): array
    {
        if ($fieldDto->getCustomOptions()->has('expanded')) {
            $formFieldOptions['value_type_options']['expanded'] = $fieldDto['expanded'];
        }
        if ($fieldDto->getCustomOptions()->has('multiple')) {
            $formFieldOptions['value_type_options']['multiple'] = $fieldDto->getCustomOptions()->has('multiple');
        }
        if ($fieldDto->getCustomOptions()->has('choices')) {
            $formFieldOptions['value_type_options']['choices'] = $fieldDto->getCustomOptions()->has('choices');
        }

        return $formFieldOptions;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(string $formTypeFqcn, array $formFieldOptions, FieldDto $fieldDto): bool
    {
        return ChoiceFilterType::class === $formTypeFqcn;
    }
}
