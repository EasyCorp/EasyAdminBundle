<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Filter\Configurator;

use Doctrine\DBAL\Types\Type;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

final class TextConfigurator
{
    public function supports(FilterDto $filterDto)
    {
        return TextFilter::class === $filterDto->getFqcn();
    }

    public function configure(FilterDto $filterDto, EntityDto $entityDto): void
    {
        if (Type::JSON === $entityDto->getPropertyMetadata($filterDto->getProperty())['type']) {
            $filterDto->setFormTypeOption('value_type', TextareaType::class);
        }
    }
}
