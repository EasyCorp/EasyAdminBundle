<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Configuration\ActionConfig;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\AssetConfig;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\CrudConfig;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfigInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\ResponseParams;
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

    public function configureResponseParams(ResponseParams $responseParams): ResponseParams;

    /** @return Response|ResponseParams */
    public function index();

    /** @return Response|ResponseParams */
    public function detail();

    /** @return Response|ResponseParams */
    public function edit();

    /** @return Response|ResponseParams */
    public function new();
}
