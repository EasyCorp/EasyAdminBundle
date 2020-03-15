<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfigInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\ResponseParameters;
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
     * @return PropertyConfigInterface[]
     */
    public function configureProperties(string $pageName): iterable;

    public function configureResponseParameters(ResponseParameters $responseParameters): ResponseParameters;

    /** @return Response|ResponseParameters */
    public function index();

    /** @return Response|ResponseParameters */
    public function detail();

    /** @return Response|ResponseParameters */
    public function edit();

    /** @return Response|ResponseParameters */
    public function new();
}
