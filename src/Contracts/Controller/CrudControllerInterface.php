<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\ActionsInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\AssetsInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\CrudInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\FiltersInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStoreInterface;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContextInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDtoInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDtoInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface CrudControllerInterface
{
    public static function getEntityFqcn(): string;

    public function configureCrud(CrudInterface $crud): CrudInterface;

    public function configureAssets(AssetsInterface $assets): AssetsInterface;

    public function configureActions(ActionsInterface $actions): ActionsInterface;

    public function configureFilters(FiltersInterface $filters): FiltersInterface;

    /**
     * @return FieldInterface[]|string[]
     *
     * @psalm-return iterable<FieldInterface|string>
     */
    public function configureFields(string $pageName): iterable;

    public function index(AdminContextInterface $context): Response|KeyValueStoreInterface;

    public function detail(AdminContextInterface $context): Response|KeyValueStoreInterface;

    public function edit(AdminContextInterface $context): Response|KeyValueStoreInterface;

    public function new(AdminContextInterface $context): Response|KeyValueStoreInterface;

    public function delete(AdminContextInterface $context): Response|KeyValueStoreInterface;

    public function autocomplete(AdminContextInterface $context): JsonResponse;

    public function configureResponseParameters(KeyValueStoreInterface $responseParameters): KeyValueStoreInterface;

    public function createIndexQueryBuilder(
        SearchDtoInterface $searchDto,
        EntityDtoInterface $entityDto,
        FieldCollection $fields,
        FilterCollection $filters
    ): QueryBuilder;

    public function createEntity(string $entityFqcn);

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void;

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void;

    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void;

    public function createEditFormBuilder(
        EntityDtoInterface $entityDto,
        KeyValueStoreInterface $formOptions,
        AdminContextInterface $context
    ): FormBuilderInterface;

    public function createEditForm(
        EntityDtoInterface $entityDto,
        KeyValueStoreInterface $formOptions,
        AdminContextInterface $context
    ): FormInterface;

    public function createNewFormBuilder(
        EntityDtoInterface $entityDto,
        KeyValueStoreInterface $formOptions,
        AdminContextInterface $context
    ): FormBuilderInterface;

    public function createNewForm(
        EntityDtoInterface $entityDto,
        KeyValueStoreInterface $formOptions,
        AdminContextInterface $context
    ): FormInterface;
}
