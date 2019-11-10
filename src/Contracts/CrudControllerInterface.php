<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Contacts;

use EasyCorp\Bundle\EasyAdminBundle\Configuration\AssetConfig;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\CrudConfig;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\DetailPageConfig;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\FieldInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface CrudControllerInterface
{
    public function configureCrud(): CrudConfig;

    public function configureAssets(): AssetConfig;

    public function configureDetailPage(): DetailPageConfig;

    /**
     * @return \EasyCorp\Bundle\EasyAdminBundle\Contracts\FieldInterface[]
     */
    public function configureFields(string $action): iterable;

    public function index(): Response;

    public function getCrudConfig(): CrudConfig;
}
