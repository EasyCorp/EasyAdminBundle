<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Filter\Configurator;

use Doctrine\DBAL\Types\Types;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

/**
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class TextConfigurator implements FilterConfiguratorInterface
{
    public function supports(FilterDto $filterDto, ?FieldDto $fieldDto, EntityDto $entityDto, AdminContext $context): bool
    {
        return TextFilter::class === $filterDto->getFqcn();
    }

    public function configure(FilterDto $filterDto, ?FieldDto $fieldDto, EntityDto $entityDto, AdminContext $context): void
    {
        $propertyType = $entityDto->getPropertyMetadata($filterDto->getProperty())->get('type');

        if (Types::JSON === $propertyType) {
            $filterDto->setFormTypeOption('value_type', TextareaType::class);
        }

        // don't use Types::OBJECT because it was removed in Doctrine ORM 3.0
        if (\in_array($propertyType, [Types::BLOB, 'object', Types::TEXT], true)) {
            $filterDto->setFormTypeOptionIfNotSet('value_type', TextareaType::class);
        }
    }
}
