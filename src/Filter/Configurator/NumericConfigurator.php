<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Filter\Configurator;

use Doctrine\DBAL\Types\Type;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

final class NumericConfigurator
{
    public function supports(FilterDto $filterDto)
    {
        return NumericFilter::class === $filterDto->getFqcn();
    }

    public function configure(FilterDto $filterDto, EntityDto $entityDto): void
    {
        $propertyType = $entityDto->getPropertyMetadata($filterDto->getProperty())['type'];

        if (Type::DECIMAL === $propertyType) {
            $filterDto->setFormTypeOption('value_type_options', ['input' => 'string']);
        }

        if (in_array($propertyType, [Type::BIGINT, Type::INTEGER, Type::SMALLINT], true)) {
            $filterDto->setFormTypeOption('value_type', IntegerType::class);
        }
    }
}
