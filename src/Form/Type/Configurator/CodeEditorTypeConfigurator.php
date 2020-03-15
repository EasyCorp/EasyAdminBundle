<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Type\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
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
    public function configure(string $name, array $formFieldOtions, FieldDto $fieldDto, FormConfigInterface $parentConfig): array
    {
        return [];

        if ($fieldDto->getCustomOptions()->has('height')) {
            $formFieldOtions['height'] = $fieldDto->getCustomOptions()->get('height');
        }
        if ($fieldDto->getCustomOptions()->has('tabSize')) {
            $formFieldOtions['tab_size'] = $fieldDto->getCustomOptions()->get('tabSize');
        }
        if ($fieldDto->getCustomOptions()->has('indentWithTabs')) {
            $formFieldOtions['indent_with_tabs'] = $fieldDto->getCustomOptions()->get('indentWithTabs');
        }
        if ($fieldDto->getCustomOptions()->has('language')) {
            $formFieldOtions['language'] = $fieldDto->getCustomOptions()->get('language');
        }

        return $formFieldOtions;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(string $formTypeFqcn, array $formFieldOptions, FieldDto $fieldDto): bool
    {
        return CodeEditorType::class === $formTypeFqcn;
    }
}
