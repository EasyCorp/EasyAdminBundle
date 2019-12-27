<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Property;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfigInterface;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\TextEditorType;

class TextEditorProperty implements PropertyConfigInterface
{
    use PropertyConfigTrait;

    public function __construct()
    {
        $this
            ->setType('text_editor')
            ->setFormType(TextEditorType::class)
            ->setTemplateName('property/text_editor')
            ->addCssFiles('bundles/easyadmin/form-type-text-editor.css')
            ->addJsFiles('bundles/easyadmin/form-type-text-editor.js');
    }
}
