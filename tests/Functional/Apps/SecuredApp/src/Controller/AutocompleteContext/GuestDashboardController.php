<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\AutocompleteContext;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Used by AutocompleteContextTest: only GuestUserCrudController is available in this dashboard.
 */
#[AdminDashboard(routePath: '/admin/autocomplete-guest', routeName: 'autocomplete_guest_admin', allowedControllers: [GuestUserCrudController::class])]
class GuestDashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        return parent::index();
    }
}
