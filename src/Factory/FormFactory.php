<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Factory;

use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\CrudBatchActionFormType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\CrudFormType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\FiltersFormType;
use EasyCorp\Bundle\EasyAdminBundle\Router\CrudUrlGenerator;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

final class FormFactory
{
    private $symfonyFormFactory;
    private $crudUrlGenerator;
    private $filterFactory;

    public function __construct(FormFactoryInterface $symfonyFormFactory, CrudUrlGenerator $crudUrlGenerator, FilterFactory $filterFactory)
    {
        $this->symfonyFormFactory = $symfonyFormFactory;
        $this->crudUrlGenerator = $crudUrlGenerator;
        $this->filterFactory = $filterFactory;
    }

    public function createEditForm(EntityDto $entityDto): FormInterface
    {
        $formTypeOptions = [
            'entityDto' => $entityDto,
            'attr' => ['id' => sprintf('edit-%s-form', $entityDto->getName())],
        ];

        return $this->symfonyFormFactory->createNamedBuilder($entityDto->getName(), CrudFormType::class, $entityDto->getInstance(), $formTypeOptions)->getForm();
    }

    public function createNewForm(EntityDto $entityDto): FormInterface
    {
        $formTypeOptions = [
            'entityDto' => $entityDto,
            'attr' => ['id' => sprintf('new-%s-form', $entityDto->getName())],
        ];

        return $this->symfonyFormFactory->createNamedBuilder($entityDto->getName(), CrudFormType::class, $entityDto->getInstance(), $formTypeOptions)->getForm();
    }

    public function createBatchActionsForm(): FormInterface
    {
        return $this->symfonyFormFactory->createNamedBuilder('batch_form', CrudBatchActionFormType::class, null, [
            'action' => $this->crudUrlGenerator->build()->setAction('batch')->generateUrl(),
        ])->getForm();
    }

    public function createFiltersForm(AdminContext $adminContext, FieldCollection $fields, EntityDto $entityDto): FormInterface
    {
        $filters = $this->filterFactory->create($adminContext->getCrud()->getFilters(), $fields, $entityDto);
        $filtersForm = $this->symfonyFormFactory->createNamed('filters', FiltersFormType::class, null, [
            'method' => 'GET',
            'action' => $adminContext->getRequest()->query->get('referrer'),
            'ea_filters' => $filters->getConfiguredFilters(),
        ]);

        return $filtersForm->handleRequest($adminContext->getRequest());
    }
}
