<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Configuration\DetailPageConfig;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\EntityAdminConfig;
use EasyCorp\Bundle\EasyAdminBundle\Field\FieldInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface EntityAdminControllerInterface
{
    public function configureEntityAdmin(): EntityAdminConfig;

    public function configureDetailPage(DetailPageConfig $config): DetailPageConfig;

    /**
     * @return FieldInterface[]
     */
    public function configureFields(string $action): iterable;

    public function index(): Response;

    public function getEntityAdminConfig(): EntityAdminConfig;
}
