<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Contacts;

use EasyCorp\Bundle\EasyAdminBundle\Configuration\ActionCollection;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\AssetConfig;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\CrudConfig;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\DetailPageConfig;
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
    public function configureFields(string $page): iterable;

    public function index(): Response;
}
