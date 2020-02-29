<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Configuration\ActionConfig;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\AssetConfig;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\CrudConfig;
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
     * @return \EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfigInterface[]
     */
    public function configureProperties(string $pageName): iterable;

    public function index(): Response;
}
