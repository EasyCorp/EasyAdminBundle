<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\AssetsInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Config\FiltersInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDtoInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
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

    public function configureCrud(Crud $crud): Crud;

    public function configureAssets(AssetsInterface $assets): AssetsInterface;

    public function configureActions(Actions $actions): Actions;

    public function configureFilters(FiltersInterface $filters): FiltersInterface;

    /**
     * @return FieldInterface[]|string[]
     *
     * @psalm-return iterable<FieldInterface|string>
     */
    public function configureFields(string $pageName): iterable;

    /** @return KeyValueStore|Response */
    public function index(AdminContext $context);

    /** @return KeyValueStore|Response */
    public function detail(AdminContext $context);

    /** @return KeyValueStore|Response */
    public function edit(AdminContext $context);

    /** @return KeyValueStore|Response */
    public function new(AdminContext $context);

    /** @return KeyValueStore|Response */
    public function delete(AdminContext $context);

    public function autocomplete(AdminContext $context): JsonResponse;

    public function configureResponseParameters(KeyValueStore $responseParameters): KeyValueStore;

    public function createIndexQueryBuilder(SearchDtoInterface $searchDto, EntityDtoInterface $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder;

    public function createEntity(string $entityFqcn);

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void;

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void;

    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void;

    public function createEditFormBuilder(EntityDtoInterface $entityDto, KeyValueStore $formOptions, AdminContext $context): FormBuilderInterface;

    public function createEditForm(EntityDtoInterface $entityDto, KeyValueStore $formOptions, AdminContext $context): FormInterface;

    public function createNewFormBuilder(EntityDtoInterface $entityDto, KeyValueStore $formOptions, AdminContext $context): FormBuilderInterface;

    public function createNewForm(EntityDtoInterface $entityDto, KeyValueStore $formOptions, AdminContext $context): FormInterface;
}
