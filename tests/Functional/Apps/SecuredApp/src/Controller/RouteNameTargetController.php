<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Routes that can be generated but not reached through EasyAdmin's '?routeName='
 * dispatch with the given query parameters, used to check that those requests
 * end in a client error instead of a 500 error. The routes with an 'audience'
 * argument check that the target controller gets its arguments from the matched
 * route, not from the '?routeParams=' values.
 */
class RouteNameTargetController
{
    #[Route('/admin/route-name-target/{id}', name: 'route_name_target_with_requirements', requirements: ['id' => '\d+'])]
    public function withRequirements(int $id): Response
    {
        return new Response((string) $id);
    }

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

    #[Route('/admin/route-name-target-post-only', name: 'route_name_target_post_only', methods: ['POST'])]
    public function postOnly(): Response
    {
        return new Response('POST');
    }

    #[Route('/admin/route-name-target-never-matching', name: 'route_name_target_never_matching', condition: 'false')]
    public function neverMatching(): Response
    {
        return new Response('UNREACHABLE');
    }
}
