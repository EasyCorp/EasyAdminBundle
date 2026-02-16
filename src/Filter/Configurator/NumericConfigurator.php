<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Filter\Configurator;

use Doctrine\DBAL\Types\Types;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

/**
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class NumericConfigurator implements FilterConfiguratorInterface
{
    public function supports(FilterDto $filterDto, ?FieldDto $fieldDto, EntityDto $entityDto, AdminContext $context): bool
    {
        return NumericFilter::class === $filterDto->getFqcn();
    }

    public function configure(FilterDto $filterDto, ?FieldDto $fieldDto, EntityDto $entityDto, AdminContext $context): void
    {
        if (!isset($entityDto->getClassMetadata()->fieldMappings[$filterDto->getProperty()])) {
            return;
        }

        $fieldMapping = $entityDto->getClassMetadata()->getFieldMapping($filterDto->getProperty());

        // @phpstan-ignore-next-line (backward compatibility with Doctrine ORM 2.x)
        $fieldType = \is_array($fieldMapping) ? ($fieldMapping['type'] ?? null) : $fieldMapping->type;

        if (Types::DECIMAL === $fieldType) {
            $filterDto->setFormTypeOptionIfNotSet('value_type_options.input', 'string');
        }

        if (\in_array($fieldType, [Types::BIGINT, Types::INTEGER, Types::SMALLINT], true)) {
            $filterDto->setFormTypeOptionIfNotSet('value_type', IntegerType::class);
        }
    }
}
