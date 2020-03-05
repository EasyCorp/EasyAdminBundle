<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Configuration\ActionConfig;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\AssetConfig;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\CrudConfig;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfigInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\ResponseParameters;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface CrudControllerInterface
{
    public function configureCrud(CrudConfig $crudConfig): CrudConfig;

    public function configureAssets(AssetConfig $assetConfig): AssetConfig;

    public function configureActions(ActionConfig $actionConfig): ActionConfig;

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
