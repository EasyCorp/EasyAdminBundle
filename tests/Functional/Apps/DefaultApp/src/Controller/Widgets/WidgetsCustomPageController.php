<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Widgets;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * A regular Symfony controller rendered inside the dashboard through '?routeName=widgets_custom_page'.
 */
class WidgetsCustomPageController extends AbstractController
{
    #[Route('/widgets-custom-page', name: 'widgets_custom_page')]
    public function __invoke(): Response
    {
        return $this->render('admin/widgets/custom_page.html.twig');
    }
}
