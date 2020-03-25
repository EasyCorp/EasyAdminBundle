<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;

final class TextEditorConfigurator implements FieldConfiguratorInterface
{
    private $adminContextProvider;

    public function __construct(AdminContextProvider $adminContextProvider)
    {
        $this->adminContextProvider = $adminContextProvider;
    }

    public function supports(FieldInterface $field, EntityDto $entityDto): bool
    {
        return $field instanceof TextEditorField;
    }

    public function configure(FieldInterface $field, EntityDto $entityDto, string $action): void
    {
        if ('rtl' === $this->adminContextProvider->getContext()->getI18n()->getTextDirection()) {
            $field->addCssFiles('bundles/easyadmin/form-type-text-editor.rtl.css');
        }
    }
}
