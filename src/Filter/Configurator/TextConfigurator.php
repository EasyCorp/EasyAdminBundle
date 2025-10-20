<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Filter\Configurator;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\FieldMapping;
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
        if (!isset($entityDto->getClassMetadata()->fieldMappings[$filterDto->getProperty()])) {
            return;
        }

        // Doctrine ORM 2.x returns an array and Doctrine ORM 3.x returns a FieldMapping object
        /** @var FieldMapping|array $fieldMapping */
        /** @phpstan-ignore-next-line */
        $fieldMapping = $entityDto->getClassMetadata()->getFieldMapping($filterDto->getProperty());
        if (\is_array($fieldMapping)) {
            $doctrineFieldMappingType = $fieldMapping['type'];
        } else {
            $doctrineFieldMappingType = $fieldMapping->type;
        }

        if (Types::JSON === $doctrineFieldMappingType) {
            $filterDto->setFormTypeOption('value_type', TextareaType::class);
        }

        // don't use Types::OBJECT because it was removed in Doctrine ORM 3.0
        if (\in_array($doctrineFieldMappingType, [Types::BLOB, 'object', Types::TEXT], true)) {
            $filterDto->setFormTypeOptionIfNotSet('value_type', TextareaType::class);
        }
    }
}
