<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class BooleanConfigurator implements FieldConfiguratorInterface
{
    private $adminUrlGenerator;

    public function __construct(AdminUrlGenerator $adminUrlGenerator)
    {
        $this->adminUrlGenerator = $adminUrlGenerator;
    }

    public function supports(FieldDto $field, EntityDto $entityDto): bool
    {
        return BooleanField::class === $field->getFieldFqcn();
    }

    public function configure(FieldDto $field, EntityDto $entityDto, AdminContext $context): void
    {
        $isRenderedAsSwitch = true === $field->getCustomOption(BooleanField::OPTION_RENDER_AS_SWITCH);
        if ($isRenderedAsSwitch && false !== strpos($field->getProperty(), '.')) {
            throw new \InvalidArgumentException(sprintf('The "%s" property cannot be rendered as a switch because it belongs to an associated entity instead of to the entity itself. Render the property as a normal boolean field.', $field->getProperty()));
        }

        if ($isRenderedAsSwitch) {
            $toggleUrl = $this->adminUrlGenerator->setAction(Action::EDIT)->setEntityId($entityDto->getPrimaryKeyValue())->set('fieldName', $field->getProperty())->generateUrl();
            $field->setCustomOption(BooleanField::OPTION_TOGGLE_URL, $toggleUrl);

            // TODO: ask someone who knows Symfony forms well how to make this work
            // see https://symfony.com/blog/new-in-symfony-4-4-bootstrap-custom-switches
            // $field->setFormTypeOptionIfNotSet('label_attr.class', 'switch-custom');
        }
    }
}
