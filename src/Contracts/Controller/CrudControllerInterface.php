<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Fields;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\ResponseParameters;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface CrudControllerInterface
{
    public function configureCrud(Crud $crud): Crud;

    public function configureAssets(Assets $assets): Assets;

    public function configureActions(Actions $actions): Actions;

    public function configureFilters(Filters $filters): Filters;

    /**
     * @return FieldInterface[]
     */
    public function configureFields(string $pageName): iterable;

    /** @return Response|ResponseParameters */
    public function index(AdminContext $context);

    /** @return Response|ResponseParameters */
    public function detail(AdminContext $context);

    /** @return Response|ResponseParameters */
    public function edit(AdminContext $context);

    /** @return Response|ResponseParameters */
    public function new(AdminContext $context);

    /** @return Response|ResponseParameters */
    public function delete(AdminContext $context);

    public function autocomplete(AdminContext $context): JsonResponse;

    public function configureResponseParameters(ResponseParameters $responseParameters): ResponseParameters;

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, Fields $fields, array $filtersDto): QueryBuilder;

    public function createEntity(string $entityFqcn);

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void;

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void;

    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void;

    public function createEditForm(EntityDto $entityDto): FormInterface;

    public function createNewForm(EntityDto $entityDto): FormInterface;
}
