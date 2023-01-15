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
 * @template TInstance of object
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface CrudControllerInterface
{
    /**
     * @return class-string<TInstance>
     */
    public static function getEntityFqcn(): string;

    public function configureCrud(Crud $crud): Crud;

    public function configureAssets(Assets $assets): Assets;

    public function configureActions(Actions $actions): Actions;

    public function configureFilters(Filters $filters): Filters;

    /**
     * @return FieldInterface[]
     *
     * @psalm-return iterable<FieldInterface>
     */
    public function configureFields(string $pageName): iterable;

    /**
     * @param AdminContext<TInstance> $context
     *
     * @return KeyValueStore|Response
     */
    public function index(AdminContext $context);

    /**
     * @param AdminContext<TInstance> $context
     *
     * @return KeyValueStore|Response
     */
    public function detail(AdminContext $context);

    /**
     * @param AdminContext<TInstance> $context
     *
     * @return KeyValueStore|Response
     */
    public function edit(AdminContext $context);

    /**
     * @param AdminContext<TInstance> $context
     *
     * @return KeyValueStore|Response
     */
    public function new(AdminContext $context);

    /**
     * @param AdminContext<TInstance> $context
     *
     * @return KeyValueStore|Response
     */
    public function delete(AdminContext $context);

    /**
     * @param AdminContext<TInstance> $context
     */
    public function autocomplete(AdminContext $context): JsonResponse;

    public function configureResponseParameters(KeyValueStore $responseParameters): KeyValueStore;

    /**
     * @param EntityDto<TInstance> $entityDto
     */
    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder;

    /**
     * @param class-string<TInstance> $entityFqcn
     *
     * @return TInstance
     */
    public function createEntity(string $entityFqcn);

    /**
     * @param TInstance $entityInstance
     */
    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void;

    /**
     * @param TInstance $entityInstance
     */
    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void;

    /**
     * @param TInstance $entityInstance
     */
    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void;

    /**
     * @param EntityDto<TInstance>    $entityDto
     * @param AdminContext<TInstance> $context
     */
    public function createEditFormBuilder(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormBuilderInterface;

    /**
     * @param EntityDto<TInstance>    $entityDto
     * @param AdminContext<TInstance> $context
     */
    public function createEditForm(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormInterface;

    /**
     * @param EntityDto<TInstance>    $entityDto
     * @param AdminContext<TInstance> $context
     */
    public function createNewFormBuilder(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormBuilderInterface;

    /**
     * @param EntityDto<TInstance>    $entityDto
     * @param AdminContext<TInstance> $context
     */
    public function createNewForm(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormInterface;
}
