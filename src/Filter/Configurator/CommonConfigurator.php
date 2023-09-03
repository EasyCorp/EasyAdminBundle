<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Filter\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDtoInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDtoInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDtoInterface;

use function Symfony\Component\String\u;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class CommonConfigurator implements FilterConfiguratorInterface
{
    public function supports(FilterDtoInterface $filterDto, ?FieldDtoInterface $fieldDto, EntityDtoInterface $entityDto, AdminContext $context): bool
    {
        return true;
    }

    public function configure(FilterDtoInterface $filterDto, ?FieldDtoInterface $fieldDto, EntityDtoInterface $entityDto, AdminContext $context): void
    {
        if (null === $filterDto->getLabel()) {
            $fieldLabel = null !== $fieldDto ? $fieldDto->getLabel() : null;
            $label = $fieldLabel ?? u($filterDto->getProperty())->title()->toString();
            $filterDto->setLabel($label);
        }
    }
}
