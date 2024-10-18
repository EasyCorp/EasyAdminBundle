<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Twig\Markup;

final class FieldActionConfigurator implements FieldConfiguratorInterface
{
    public function __construct(
        private AdminUrlGenerator $adminUrlGenerator,
        private string $charset,
    ) {
    }

    public function supports(FieldDto $field, EntityDto $entityDto): bool
    {
        return null !== $field->getAction();
    }

    public function configure(FieldDto $field, EntityDto $entityDto, AdminContext $context): void
    {
        if (Crud::PAGE_INDEX !== $context->getCrud()->getCurrentPage()) {
            return;
        }

        $url = $this->adminUrlGenerator
            ->setAction($field->getAction())
            ->setEntityId($entityDto->getPrimaryKeyValue())
            ->generateUrl()
        ;

        $formattedValue = sprintf('<a href="%s">%s</a>', $url, $field->getFormattedValue());
        $field->setFormattedValue(new Markup($formattedValue, $this->charset));
    }
}
