<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Dto\PropertyDto;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\ChoiceFilterType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\Configurator\TypeConfiguratorInterface;
use Symfony\Component\Form\FormConfigInterface;

/**
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
class ChoiceFilterTypeConfigurator implements TypeConfiguratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function configure(string $name, array $formFieldOptions, PropertyDto $propertyDto, FormConfigInterface $parentConfig): array
    {
        if ($propertyDto->getCustomOptions()->has('expanded')) {
            $formFieldOtions['value_type_options']['expanded'] = $propertyDto['expanded'];
        }
        if ($propertyDto->getCustomOptions()->has('multiple')) {
            $formFieldOtions['value_type_options']['multiple'] = $propertyDto->getCustomOptions()->has('multiple');
        }
        if ($propertyDto->getCustomOptions()->has('choices')) {
            $formFieldOtions['value_type_options']['choices'] = $propertyDto->getCustomOptions()->has('choices');
        }

        return $formFieldOtions;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(string $formTypeFqcn, array $formFieldOptions, PropertyDto $propertyDto): bool
    {
        return ChoiceFilterType::class === $formTypeFqcn;
    }
}
