<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Plain Symfony routes reached through EasyAdmin's '?routeName=' dispatch, used to
 * check that the target controller gets its arguments from the matched route, not
 * from the '?routeParams=' values.
 */
class RouteNameTargetController
{
    #[Route('/admin/route-name-target-with-defaults', name: 'route_name_target_with_defaults', defaults: ['audience' => 'public'])]
    public function withDefaults(string $audience): Response
    {
        return new Response($audience);
    }

    #[Route('/admin/route-name-target-with-optional-argument', name: 'route_name_target_with_optional_argument')]
    public function withOptionalArgument(string $audience = 'public'): Response
    {
        return new Response($audience);
    }
}
