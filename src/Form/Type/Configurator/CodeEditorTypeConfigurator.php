<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Type\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Dto\PropertyDto;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\CodeEditorType;
use Symfony\Component\Form\FormConfigInterface;

/**
 * This configurator is applied to any form field of type 'code_editor'.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class CodeEditorTypeConfigurator implements TypeConfiguratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function configure(string $name, array $formFieldOtions, PropertyDto $propertyDto, FormConfigInterface $parentConfig): array
    {
        if ($propertyDto->getCustomOptions()->has('height')) {
            $formFieldOtions['height'] = $propertyDto->getCustomOptions()->get('height');
        }
        if ($propertyDto->getCustomOptions()->has('tabSize')) {
            $formFieldOtions['tab_size'] = $propertyDto->getCustomOptions()->get('tabSize');
        }
        if ($propertyDto->getCustomOptions()->has('indentWithTabs')) {
            $formFieldOtions['indent_with_tabs'] = $propertyDto->getCustomOptions()->get('indentWithTabs');
        }
        if ($propertyDto->getCustomOptions()->has('language')) {
            $formFieldOtions['language'] = $propertyDto->getCustomOptions()->get('language');
        }

        return $formFieldOtions;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(string $formTypeFqcn, array $formFieldOptions, PropertyDto $propertyDto): bool
    {
        return CodeEditorType::class === $formTypeFqcn;
    }
}
