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

        // In Doctrine ORM 3.x, FieldMapping implements \ArrayAccess; in 4.x it's an object with properties
        $fieldMapping = $entityDto->getClassMetadata()->getFieldMapping($filterDto->getProperty());
        // In Doctrine ORM 2.x, getFieldMapping() returns an array
        /** @phpstan-ignore-next-line function.impossibleType */
        if (\is_array($fieldMapping)) {
            /** @phpstan-ignore-next-line cast.useless */
            $fieldMapping = (object) $fieldMapping;
        }
        /** @phpstan-ignore-next-line function.alreadyNarrowedType */
        $fieldType = property_exists($fieldMapping, 'type') ? $fieldMapping->type : $fieldMapping['type'];

        if (Types::JSON === $fieldType) {
            $filterDto->setFormTypeOption('value_type', TextareaType::class);
        }

        // don't use Types::OBJECT because it was removed in Doctrine ORM 3.0
        if (\in_array($fieldType, [Types::BLOB, 'object', Types::TEXT], true)) {
            $filterDto->setFormTypeOptionIfNotSet('value_type', TextareaType::class);
        }
    }
}
