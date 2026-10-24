<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Performance;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class NonAdminPageController
{
    // the argument with a default value makes Symfony call all the argument value resolvers
    #[Route('/non-admin-page', name: 'non_admin_page')]
    public function page(Request $request, int $page = 1): Response
    {
        return new Response(sprintf('Page %d of %s', $page, $request->getPathInfo()));
    }
}
