<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use function Symfony\Component\String\u;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class IdConfigurator implements FieldConfiguratorInterface
{
    private $adminUrlGenerator;

    public function __construct(AdminUrlGenerator $adminUrlGenerator)
    {
        $this->adminUrlGenerator = $adminUrlGenerator;
    }

    public function supports(FieldDto $field, EntityDto $entityDto): bool
    {
        return IdField::class === $field->getFieldFqcn();
    }

    public function configure(FieldDto $field, EntityDto $entityDto, AdminContext $context): void
    {
        $maxLength = $field->getCustomOption(IdField::OPTION_MAX_LENGTH);
        if (null === $maxLength) {
            $maxLength = Crud::PAGE_INDEX === $context->getCrud()->getCurrentPage() ? 7 : -1;
        }

        if (-1 !== $maxLength && null !== $field->getValue()) {
            $field->setFormattedValue(u($field->getValue())->truncate($maxLength, '…')->toString());
        }

        $asLink = $field->getCustomOption(IdField::OPTION_AS_LINK);
        if ($asLink) {
            $field->setCustomOption(IdField::OPTION_ENTITY_URL, $this->generateLinkToEntity($entityDto));
        }
    }

    private function generateLinkToEntity(EntityDto $entityDto): ?string
    {
        return $this->adminUrlGenerator
            ->setAction(Action::DETAIL)
            ->setEntityId($entityDto->getPrimaryKeyValue())
            ->unset(EA::MENU_INDEX)
            ->unset(EA::SUBMENU_INDEX)
            ->includeReferrer()
            ->generateUrl();
    }
}
