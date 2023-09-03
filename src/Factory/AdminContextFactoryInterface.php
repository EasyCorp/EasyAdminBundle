<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Factory;


use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\CrudControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\DashboardControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\CrudDtoInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDtoInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface AdminContextFactoryInterface
{
    public function create(
        Request $request,
        DashboardControllerInterface $dashboardController,
        ?CrudControllerInterface $crudController
    ): AdminContext;

    public function getSearchDto(Request $request, ?CrudDtoInterface $crudDto): ?SearchDtoInterface;
}
