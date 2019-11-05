<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Configuration\AssetConfig;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\DetailPageConfig;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\CrudConfig;
use EasyCorp\Bundle\EasyAdminBundle\Field\FieldInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface CrudControllerInterface
{
    public function configureCrud(): CrudConfig;

    public function configureAssets(): AssetConfig;

    public function configureDetailPage(DetailPageConfig $config): DetailPageConfig;

    /**
     * @return FieldInterface[]
     */
    public function configureFields(string $action): iterable;

    public function index(): Response;

    public function getCrudConfig(): CrudConfig;
}
