<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Factory;

use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\CrudBatchActionFormType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\CrudFormType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\FiltersFormType;
use EasyCorp\Bundle\EasyAdminBundle\Router\CrudUrlGenerator;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

final class FormFactory
{
    private $adminContextProvider;
    private $symfonyFormFactory;
    private $crudUrlGenerator;

    public function __construct(AdminContextProvider $adminContextProvider, FormFactoryInterface $symfonyFormFactory, CrudUrlGenerator $crudUrlGenerator)
    {
        $this->adminContextProvider = $adminContextProvider;
        $this->symfonyFormFactory = $symfonyFormFactory;
        $this->crudUrlGenerator = $crudUrlGenerator;
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

    public function createFilterForm(): FormInterface
    {
        $adminContext = $this->adminContextProvider->getContext();
        $filtersForm = $this->symfonyFormFactory->createNamed('filters', FiltersFormType::class, null, [
            'method' => 'GET',
            'action' => $adminContext->getRequest()->query->get('referrer'),
        ]);

        return $filtersForm->handleRequest($adminContext->getRequest());
    }
}
