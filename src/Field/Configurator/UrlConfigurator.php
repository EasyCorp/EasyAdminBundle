<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use function Symfony\Component\String\u;

final class UrlConfigurator implements FieldConfiguratorInterface
{
    public function supports(FieldInterface $field, EntityDto $entityDto): bool
    {
        return $field instanceof UrlField;
    }

    public function configure(FieldInterface $field, EntityDto $entityDto, string $action): void
    {
        $formTypeOptions = $field->getFormTypeOptions();
        $formTypeOptions['attr']['inputmode'] = $formTypeOptions['attr']['inputmode'] ?? 'url';
        $field->setFormTypeOptions($formTypeOptions);

        $prettyUrl = str_replace(['http://www.', 'https://www.', 'http://', 'https://'], '', $field->getValue());
        $prettyUrl = rtrim($prettyUrl, '/');

        if (Action::INDEX === $action) {
            $prettyUrl = u($prettyUrl)->truncate(32, '…');
        }

        $field->setFormattedValue($prettyUrl);
    }
}
