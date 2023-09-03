<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Filter\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDtoInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDtoInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDtoInterface;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NullFilter;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class NullConfigurator implements FilterConfiguratorInterface
{
    public function supports(
        FilterDtoInterface $filterDto,
        ?FieldDtoInterface $fieldDto,
        EntityDtoInterface $entityDto,
        AdminContext $context
    ): bool {
        return NullFilter::class === $filterDto->getFqcn();
    }

    public function configure(
        FilterDtoInterface $filterDto,
        ?FieldDtoInterface $fieldDto,
        EntityDtoInterface $entityDto,
        AdminContext $context
    ): void {
        $choices = $filterDto->getFormTypeOption('choices');

        if (null === $choices || 0 === \count($choices)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'The Null filter associated to the "%s" property does not define the labels of the NULL and NOT NULL options. Define them with the setChoiceLabels() method.',
                    $filterDto->getProperty()
                )
            );
        }
    }
}
