<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Filter\Configurator;

use Doctrine\ORM\Mapping\JoinColumnMapping;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\CrudAutocompleteType;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGeneratorInterface;

/**
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class EntityConfigurator implements FilterConfiguratorInterface
{
    public function __construct(
        private AdminUrlGeneratorInterface $adminUrlGenerator,
    ) {
    }

    public function supports(FilterDto $filterDto, ?FieldDto $fieldDto, EntityDto $entityDto, AdminContext $context): bool
    {
        return EntityFilter::class === $filterDto->getFqcn();
    }

    public function configure(FilterDto $filterDto, ?FieldDto $fieldDto, EntityDto $entityDto, AdminContext $context): void
    {
        $propertyName = $filterDto->getProperty();
        if (!$entityDto->getClassMetadata()->hasAssociation($propertyName)) {
            return;
        }

        // TODO: add the 'em' form type option too?
        $filterDto->setFormTypeOptionIfNotSet('value_type_options.class', $entityDto->getClassMetadata()->getAssociationTargetClass($propertyName));
        $filterDto->setFormTypeOptionIfNotSet('value_type_options.multiple', $entityDto->getClassMetadata()->isCollectionValuedAssociation($propertyName));
        $filterDto->setFormTypeOptionIfNotSet('value_type_options.attr.data-ea-widget', 'ea-autocomplete');

        $supportsAutocomplete = true === $filterDto->getFormTypeOption('autocomplete');
        if ($entityDto->getClassMetadata()->isSingleValuedAssociation($propertyName)) {
            $associationMapping = $entityDto->getClassMetadata()->associationMappings[$propertyName];
            // don't show the 'empty value' placeholder when all join columns are required,
            // because an empty filter value would always return no result
            $numberOfRequiredJoinColumns = \count(array_filter(
                $associationMapping['joinColumns'],
                static function (array|JoinColumnMapping $joinColumn): bool {
                    // Doctrine ORM 3.x changed the returned type from array to JoinColumnMapping
                    if ($joinColumn instanceof JoinColumnMapping) {
                        $isNullable = $joinColumn->nullable ?? false;
                    } else {
                        $isNullable = $joinColumn['nullable'] ?? false;
                    }

                    return false === $isNullable;
                }
            ));

            $someJoinColumnsAreNullable = \count($associationMapping['joinColumns']) !== $numberOfRequiredJoinColumns;

            if ($someJoinColumnsAreNullable && false === $supportsAutocomplete) {
                $filterDto->setFormTypeOptionIfNotSet('value_type_options.placeholder', 'label.form.empty_value');
            }
        }

        if ($supportsAutocomplete) {
            $associationMapping = $entityDto->getClassMetadata()->associationMappings[$propertyName];
            $targetEntityFqcn = $associationMapping['targetEntity'];
            if (null === $targetCrudControllerFqcn = $context->getCrudControllers()->findCrudFqcnByEntityFqcn($targetEntityFqcn)) {
                throw new \LogicException('The target CRUD controller for the entity '.$targetEntityFqcn.' is not defined.');
            }
            $autocompleteEndpointUrl = $this->adminUrlGenerator
                ->unsetAll()
                ->set('page', 1)
                ->setController($targetCrudControllerFqcn)
                ->setAction('autocomplete')
                ->set('autocompleteContext', [
                    'propertyName' => $propertyName,
                    'originatingPage' => $context->getCrud()?->getCurrentAction() ?? throw new \LogicException('The current action is not defined.'),
                ])
                ->generateUrl()
            ;

            $filterDto->setFormTypeOptionIfNotSet('value_type', CrudAutocompleteType::class);
            $filterDto->setFormTypeOptionIfNotSet('value_type_options.class', $targetEntityFqcn);
            $filterDto->setFormTypeOptionIfNotSet('value_type_options.attr.data-widget', 'select2');
            $filterDto->setFormTypeOption('value_type_options.attr.data-ea-autocomplete-endpoint-url', $autocompleteEndpointUrl);
        }
    }
}
