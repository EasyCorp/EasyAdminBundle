<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Type\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use Symfony\Component\Form\FormConfigInterface;

/**
 * This configurator is applied to any form field of type 'fos_ckeditor' available
 * when using the FOSCKEditorBundle. It's used to provide a better default
 * configuration for the WYSIWYG editors created with this bundle.
 *
 * @author Maxime Steinhausser <maxime.steinhausser@gmail.com>
 */
class FOSCKEditorTypeConfigurator implements TypeConfiguratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function configure(string $name, array $formFieldOptions, FieldDto $fieldDto, FormConfigInterface $parentConfig): array
    {
        // when the IvoryCKEditor doesn't define the toolbar to use, EasyAdmin uses a simple toolbar
        if (!isset($formFieldOptions['config']['toolbar']) && !isset($formFieldOptions['config_name'])) {
            $formFieldOptions['config']['toolbar'] = [
                ['name' => 'styles', 'items' => ['Bold', 'Italic', 'Strike', 'Link']],
                ['name' => 'lists', 'items' => ['BulletedList', 'NumberedList', '-', 'Outdent', 'Indent']],
                ['name' => 'clipboard', 'items' => ['Copy', 'Paste', 'PasteFromWord', '-', 'Undo', 'Redo']],
                ['name' => 'advanced', 'items' => ['Source']],
            ];
        }

        return $formFieldOptions;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(string $formTypeFqcn, array $formFieldOptions, FieldDto $fieldDto): bool
    {
        return 'FOS\\CKEditorBundle\\Form\\Type\\CKEditorType' == $formTypeFqcn;
    }
}
