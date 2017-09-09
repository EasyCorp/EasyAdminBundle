<?php

namespace JavierEguiluz\Bundle\EasyAdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

@trigger_error('The '.__NAMESPACE__.'\AdminController class is deprecated since version 1.16 and will be removed in 2.0. Use the EasyCorp\Bundle\EasyAdminBundle\Controller\AdminController class instead.', E_USER_DEPRECATED);

class_exists('EasyCorp\Bundle\EasyAdminBundle\Controller\AdminController');

if (\false) {
    class AdminController extends Controller
    {
    }
}
