<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Contacts;

use EasyCorp\Bundle\EasyAdminBundle\Configuration\AssetConfig;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\CrudConfig;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\DetailPageConfig;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\FormPageConfig;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\IndexPageConfig;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface CrudControllerInterface
{
    public function configureCrud(CrudConfig $crudConfig): CrudConfig;

    public function configureAssets(): AssetConfig;

    public function configureIndexPage(IndexPageConfig $indexPageConfig): IndexPageConfig;
    public function configureDetailPage(DetailPageConfig $detailPageConfig): DetailPageConfig;
    public function configureFormPage(FormPageConfig $formPageConfig): FormPageConfig;

    /**
     * @return \EasyCorp\Bundle\EasyAdminBundle\Contracts\PropertyInterface[]
     */
    public function configureProperties(string $page): iterable;

    public function index(): Response;
}
