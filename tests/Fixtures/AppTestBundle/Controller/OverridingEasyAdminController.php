<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Fixtures\AppTestBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OverridingEasyAdminController extends Controller
{
    /**
     * @Route("/override_layout", name="override_layout")
     *
     * @return Response
     */
    public function overrideLayout()
    {
        return $this->render('override_templates/layout.html.twig');
    }
}
