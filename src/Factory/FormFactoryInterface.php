<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Factory;


use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDtoInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface FormFactoryInterface
{
    public function createEditFormBuilder(
        EntityDtoInterface $entityDto,
        KeyValueStore $formOptions,
        AdminContext $context
    ): FormBuilderInterface;

    public function createEditForm(
        EntityDtoInterface $entityDto,
        KeyValueStore $formOptions,
        AdminContext $context
    ): FormInterface;

    public function createNewFormBuilder(
        EntityDtoInterface $entityDto,
        KeyValueStore $formOptions,
        AdminContext $context
    ): FormBuilderInterface;

    public function createNewForm(
        EntityDtoInterface $entityDto,
        KeyValueStore $formOptions,
        AdminContext $context
    ): FormInterface;

    public function createFiltersForm(FilterCollection $filters, Request $request): FormInterface;
}
