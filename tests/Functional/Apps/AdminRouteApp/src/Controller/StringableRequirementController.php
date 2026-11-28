<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\AdminRouteApp\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\AdminRouteApp\Enum\ReportFormat;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Requirement\EnumRequirement;

/**
 * Controller whose #[AdminRoute] attribute defines a requirement with a Stringable
 * object, like the #[Route] attribute of Symfony allows.
 */
#[AdminRoute(
    path: '/stringable-requirement/{format}',
    name: 'stringable_requirement',
    options: ['requirements' => ['format' => new EnumRequirement(ReportFormat::class)]],
)]
class StringableRequirementController extends AbstractController
{
    public function __invoke(string $format): Response
    {
        return new Response('Format: '.$format);
    }
}
