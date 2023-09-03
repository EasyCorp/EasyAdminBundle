<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Filter\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDtoInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDtoInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDtoInterface;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class ChoiceConfigurator implements FilterConfiguratorInterface
{
    public function supports(
        FilterDtoInterface $filterDto,
        ?FieldDtoInterface $fieldDto,
        EntityDtoInterface $entityDto,
        AdminContext $context
    ): bool {
        return ChoiceFilter::class === $filterDto->getFqcn();
    }

    public function configure(
        FilterDtoInterface $filterDto,
        ?FieldDtoInterface $fieldDto,
        EntityDtoInterface $entityDto,
        AdminContext $context
    ): void {
        $choices = $filterDto->getFormTypeOption('value_type_options.choices');

        if (null === $choices || 0 === \count($choices)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'The choice filter associated to the "%s" property does not define its choices. Define them with the setChoices() method.',
                    $filterDto->getProperty()
                )
            );
        }
    }
}
