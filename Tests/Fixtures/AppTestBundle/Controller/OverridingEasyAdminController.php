<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Fixtures\AppTestBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

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
