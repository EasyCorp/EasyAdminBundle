<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * @template TEntity of object
 */
interface CrudControllerInterface
{
    /**
     * @return class-string<TEntity>
     */
    public static function getEntityFqcn(): string;

    public function configureCrud(Crud $crud): Crud;

    public function configureAssets(Assets $assets): Assets;

    public function configureActions(Actions $actions): Actions;

    public function configureFilters(Filters $filters): Filters;

    /**
     * @return FieldInterface[]|string[]
     *
     * @phpstan-return iterable<FieldInterface|string>
     */
    public function configureFields(string $pageName): iterable;

    /**
     * @param AdminContext<TEntity> $context
     *
     * @return KeyValueStore|Response
     */
    public function index(AdminContext $context);

    /**
     * @param AdminContext<TEntity> $context
     *
     * @return KeyValueStore|Response
     */
    public function detail(AdminContext $context);

    /**
     * @param AdminContext<TEntity> $context
     *
     * @return KeyValueStore|Response
     */
    public function edit(AdminContext $context);

    /**
     * @param AdminContext<TEntity> $context
     *
     * @return KeyValueStore|Response
     */
    public function new(AdminContext $context);

    /**
     * @param AdminContext<TEntity> $context
     *
     * @return KeyValueStore|Response
     */
    public function delete(AdminContext $context);

    /**
     * @param AdminContext<TEntity> $context
     */
    public function autocomplete(AdminContext $context): JsonResponse;

    public function configureResponseParameters(KeyValueStore $responseParameters): KeyValueStore;

    /**
     * @param EntityDto<TEntity> $entityDto
     */
    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder;

    /**
     * @param class-string<TEntity> $entityFqcn
     *
     * @return object
     *
     * @phpstan-return TEntity
     */
    public function createEntity(string $entityFqcn);

    /**
     * @param TEntity $entityInstance
     */
    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void;

    /**
     * @param TEntity $entityInstance
     */
    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void;

    /**
     * @param TEntity $entityInstance
     */
    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void;

    /**
     * @param EntityDto<TEntity>    $entityDto
     * @param AdminContext<TEntity> $context
     */
    public function createEditFormBuilder(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormBuilderInterface;

    /**
     * @param EntityDto<TEntity>    $entityDto
     * @param AdminContext<TEntity> $context
     */
    public function createEditForm(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormInterface;

    /**
     * @param EntityDto<TEntity>    $entityDto
     * @param AdminContext<TEntity> $context
     */
    public function createNewFormBuilder(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormBuilderInterface;

    /**
     * @param EntityDto<TEntity>    $entityDto
     * @param AdminContext<TEntity> $context
     */
    public function createNewForm(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormInterface;
}
