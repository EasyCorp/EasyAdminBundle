<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Factory;

use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\CrudFormType;
use EasyCorp\Bundle\EasyAdminBundle\Router\CrudUrlGenerator;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

final class FormFactory
{
    private $symfonyFormFactory;
    private $crudUrlGenerator;

    public function __construct(FormFactoryInterface $symfonyFormFactory, CrudUrlGenerator $crudUrlGenerator)
    {
        $this->symfonyFormFactory = $symfonyFormFactory;
        $this->crudUrlGenerator = $crudUrlGenerator;
    }

    public function createDeleteForm(array $queryParams = []): FormInterface
    {
        $formActionUrl = $this->crudUrlGenerator->generate(array_merge(['crudAction' => 'delete'], $queryParams));

        return $this->symfonyFormFactory->createNamedBuilder('ea_delete_form')
            ->setAction($formActionUrl)
            ->setMethod('DELETE')
            ->add('submit', SubmitType::class, [
                'label' => 'delete_modal.action',
                'translation_domain' => 'EasyAdminBundle',
            ])
            // needed to avoid submitting empty delete forms (see issue #1409)
            ->add('ea_delete_flag', HiddenType::class, ['data' => '1'])
            ->getForm();
    }

    public function createEditForm(EntityDto $entityDto): FormInterface
    {
        return $this->symfonyFormFactory->createNamedBuilder($entityDto->getName(), CrudFormType::class, $entityDto->getInstance(), ['entityDto' => $entityDto])->getForm();
    }

    public function createNewForm(EntityDto $entityDto): FormInterface
    {
        return $this->symfonyFormFactory->createNamedBuilder($entityDto->getName(), CrudFormType::class, $entityDto->getInstance(), ['entityDto' => $entityDto])->getForm();
    }
}
