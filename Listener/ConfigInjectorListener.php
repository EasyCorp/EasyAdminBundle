<?php


namespace JavierEguiluz\Bundle\EasyAdminBundle\Listener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class ConfigInjectorListener
{
    private $config;
    private $routes;
    private $twig;

    public function __construct(array $config, array $routes = array(), \Twig_Environment $twig)
    {
        $this->config = $config;
        $this->routes = $routes;
        $this->twig = $twig;
    }

    public function injectConfig(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if(in_array($request->get('_route'), $this->routes)) {
            $this->twig->addGlobal('config', $this->config);
        }
    }

}
