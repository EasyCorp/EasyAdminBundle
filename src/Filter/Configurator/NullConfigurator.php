<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Filter\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NullFilter;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class NullConfigurator implements FilterConfiguratorInterface
{
    public function supports(FilterDto $filterDto, ?FieldDto $fieldDto, EntityDto $entityDto, AdminContext $context): bool
    {
        return NullFilter::class === $filterDto->getFqcn();
    }

    public function configure(FilterDto $filterDto, ?FieldDto $fieldDto, EntityDto $entityDto, AdminContext $context): void
    {
        if (empty($filterDto->getFormTypeOption('choices'))) {
            throw new \InvalidArgumentException(sprintf('The Null filter associated to the "%s" property does not define the labels of the NULL and NOT NULL options. Define them with the setChoiceLabels() method.', $filterDto->getProperty()));
        }
    }
}
