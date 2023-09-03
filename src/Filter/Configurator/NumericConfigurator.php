<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Filter\Configurator;

use Doctrine\DBAL\Types\Types;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDtoInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDtoInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDtoInterface;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

/**
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class NumericConfigurator implements FilterConfiguratorInterface
{
    public function supports(FilterDtoInterface $filterDto, ?FieldDtoInterface $fieldDto, EntityDtoInterface $entityDto, AdminContext $context): bool
    {
        return NumericFilter::class === $filterDto->getFqcn();
    }

    public function configure(FilterDtoInterface $filterDto, ?FieldDtoInterface $fieldDto, EntityDtoInterface $entityDto, AdminContext $context): void
    {
        $propertyType = $entityDto->getPropertyMetadata($filterDto->getProperty())->get('type');

        if (Types::DECIMAL === $propertyType) {
            $filterDto->setFormTypeOptionIfNotSet('value_type_options.input', 'string');
        }

        if (\in_array($propertyType, [Types::BIGINT, Types::INTEGER, Types::SMALLINT], true)) {
            $filterDto->setFormTypeOptionIfNotSet('value_type', IntegerType::class);
        }
    }
}
