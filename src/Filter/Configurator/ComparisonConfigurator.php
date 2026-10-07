<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Filter\Configurator;

use Doctrine\DBAL\Types\Types;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Orm\NestedAssociationResolverInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ComparisonFilter;
use Symfony\Component\Form\Extension\Core\Type\DateIntervalType;

/**
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class ComparisonConfigurator implements FilterConfiguratorInterface
{
    use ResolveMappedPropertyTrait;

    public function __construct(
        private readonly ?NestedAssociationResolverInterface $associationResolver = null,
    ) {
        if (null === $associationResolver) {
            self::triggerMissingResolverDeprecation();
        }
    }

    public function supports(FilterDto $filterDto, ?FieldDto $fieldDto, EntityDto $entityDto, AdminContext $context): bool
    {
        return ComparisonFilter::class === $filterDto->getFqcn();
    }

    public function configure(FilterDto $filterDto, ?FieldDto $fieldDto, EntityDto $entityDto, AdminContext $context): void
    {
        if (null === $resolvedProperty = $this->resolveMappedProperty($entityDto, $filterDto->getProperty())) {
            return;
        }

        $fieldMapping = $resolvedProperty->getEntityDto()->getClassMetadata()->getFieldMapping($resolvedProperty->getPropertyName());

        // @phpstan-ignore-next-line (backward compatibility with Doctrine ORM 2.x)
        $fieldType = \is_array($fieldMapping) ? ($fieldMapping['type'] ?? null) : $fieldMapping->type;

        if (Types::DATEINTERVAL === $fieldType) {
            $filterDto->setFormTypeOption('value_type', DateIntervalType::class);
            $filterDto->setFormTypeOption('comparison_type_options.type', 'datetime');
        }
    }
}
