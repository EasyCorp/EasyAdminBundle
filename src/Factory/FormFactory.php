<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Factory;

use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\CrudFormType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\FiltersFormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class FormFactory
{
    private $symfonyFormFactory;

    public function __construct(FormFactoryInterface $symfonyFormFactory)
    {
        $this->symfonyFormFactory = $symfonyFormFactory;
    }

    public function createEditFormBuilder(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormBuilderInterface
    {
        $cssClass = sprintf('ea-%s-form', $context->getCrud()->getCurrentAction());
        $formOptions->set('attr.class', trim(($formOptions->get('attr.class') ?? '').' '.$cssClass));
        $formOptions->set('attr.id', sprintf('edit-%s-form', $entityDto->getName()));
        $formOptions->set('entityDto', $entityDto);
        $formOptions->setIfNotSet('translation_domain', $context->getI18n()->getTranslationDomain());

        return $this->symfonyFormFactory->createNamedBuilder($entityDto->getName(), CrudFormType::class, $entityDto->getInstance(), $formOptions->all());
    }

    public function createEditForm(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormInterface
    {
        return $this->createEditFormBuilder($entityDto, $formOptions, $context)->getForm();
    }

    public function createNewFormBuilder(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormBuilderInterface
    {
        $cssClass = sprintf('ea-%s-form', $context->getCrud()->getCurrentAction());
        $formOptions->set('attr.class', trim(($formOptions->get('attr.class') ?? '').' '.$cssClass));
        $formOptions->set('attr.id', sprintf('new-%s-form', $entityDto->getName()));
        $formOptions->set('entityDto', $entityDto);
        $formOptions->setIfNotSet('translation_domain', $context->getI18n()->getTranslationDomain());

        return $this->symfonyFormFactory->createNamedBuilder($entityDto->getName(), CrudFormType::class, $entityDto->getInstance(), $formOptions->all());
    }

    public function createNewForm(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormInterface
    {
        return $this->createNewFormBuilder($entityDto, $formOptions, $context)->getForm();
    }

    public function createFiltersForm(FilterCollection $filters, Request $request): FormInterface
    {
        $filtersForm = $this->symfonyFormFactory->createNamed('filters', FiltersFormType::class, null, [
            'method' => 'GET',
            'action' => $request->query->get(EA::REFERRER, ''),
            'ea_filters' => $this->normalizeFilters($filters),
        ]);

        return $filtersForm->handleRequest($request);
    }

    /**
     * @internal
     *
     * This method is called to normalize embedded property filters.
     */
    private function normalizeFilters(FilterCollection $filters): FilterCollection
    {
        $normalizedFilters = FilterCollection::new();

        foreach($filters as $filterDto) {
            $propertyName = $filterDto->getProperty();

            // If the target property does NOT contain dots (no embedded property)
            if(false === strpos($propertyName, '.')) {
                // We change nothing.
                $normalizedFilters[$propertyName] = $filterDto;

                continue;
            }

            // We clone the filter just for the form
            $normalizedFilterDto = clone $filterDto;
            $propertyPath = $filterDto->getFormTypeOption('property_path');

            if (!$propertyPath) {
                // The property accessor sets values on array.
                // So we must replace object path to array path.
                $paths = explode('.', $filterDto->getProperty());
                foreach($paths as $key => $path) {
                    $paths[$key] = "[$path]";
                }

                // We set the property path as form option
                $normalizedFilterDto->setFormTypeOption('property_path', implode('', $paths));
            }

            // For the property name, we must replace dots by underscore to be allowed from Symfony form
            // AND match with normalized filter property name from the method FilterDto::__toString()
            $propertyName = str_replace('.', '_', $filterDto->getProperty());
            $normalizedFilterDto->setProperty($propertyName);

            $normalizedFilters[$propertyName] = $normalizedFilterDto;
        }

        return $normalizedFilters;
    }
}
