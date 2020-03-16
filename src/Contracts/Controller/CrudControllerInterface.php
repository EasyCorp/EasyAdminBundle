<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
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

    /**
     * @return FieldInterface[]
     */
    public function configureFields(string $pageName): iterable;

    /** @return Response|ResponseParameters */
    public function index();

    /** @return Response|ResponseParameters */
    public function detail();

    /** @return Response|ResponseParameters */
    public function edit();

    /** @return Response|ResponseParameters */
    public function new();

    /** @return Response|ResponseParameters */
    public function delete();

    public function autocomplete(): JsonResponse;

    public function configureResponseParameters(ResponseParameters $responseParameters): ResponseParameters;

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto): QueryBuilder;

    public function createEntity(string $entityFqcn);

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void;

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void;

    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void;

    public function createEditForm(EntityDto $entityDto): FormInterface;

    public function createNewForm(EntityDto $entityDto): FormInterface;
}
