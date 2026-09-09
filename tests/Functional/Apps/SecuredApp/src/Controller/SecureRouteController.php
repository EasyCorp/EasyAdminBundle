<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * A plain Symfony route whose path is covered by the '^/admin/secure' access_control
 * rule (ROLE_ADMIN). It is intentionally NOT wired into any EasyAdmin dashboard/CRUD
 * action, so it can only be reached by a user with the required role. It is used to
 * verify that reaching it through EasyAdmin's '?routeName=' dispatch still honors the
 * access_control rule.
 */
class SecureRouteController
{
    public const SECRET = 'SECRET_REACHED';

    #[Route('/admin/secure/danger-zone', name: 'secure_danger_zone')]
    public function dangerZone(): Response
    {
        return new Response(self::SECRET);
    }
}
