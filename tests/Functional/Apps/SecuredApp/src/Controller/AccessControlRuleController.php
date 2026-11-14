<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Plain Symfony routes covered by access_control rules of SecuredApp that combine
 * attributes handled by different voters or that match on route attributes instead of
 * on the path. They are not linked from any dashboard, so they are only reached through
 * '?routeName=' by hand.
 */
class AccessControlRuleController
{
    public const SECRET = 'ACCESS_CONTROL_RULE_REACHED';

    #[Route('/admin/access-control/role-and-full-login', name: 'access_control_role_and_full_login')]
    public function roleAndFullLogin(): Response
    {
        return new Response(self::SECRET);
    }

    #[Route('/admin/access-control/role-and-custom-voter', name: 'access_control_role_and_custom_voter')]
    public function roleAndCustomVoter(): Response
    {
        return new Response(self::SECRET);
    }

    #[Route('/admin/access-control/controller-attribute', name: 'access_control_controller_attribute')]
    public function controllerAttribute(): Response
    {
        return new Response(self::SECRET);
    }

    #[Route('/admin/access-control/route-option', name: 'access_control_route_option')]
    public function routeOption(): Response
    {
        return new Response(self::SECRET);
    }
}
