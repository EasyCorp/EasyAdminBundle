<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JavierEguiluz\Bundle\EasyAdminBundle\Configuration;

/**
 * Processes the custom CSS styles applied to the backend design based on the
 * value of the design configuration options.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class DesignConfigPass implements ConfigPassInterface
{
    /** @var \Twig_Environment */
    private $twig;

    public function __construct()
    {
        // it's not possible to inject the 'twig' service because it's synthetic
        $loader = new \Twig_Loader_Filesystem(__DIR__.'/../Resources/views/css');
        $this->twig = new \Twig_Environment($loader);
    }

    public function process(array $backendConfig)
    {
        $backendConfig = $this->processCustomCss($backendConfig);

        return $backendConfig;
    }

    private function processCustomCss(array $backendConfig)
    {
        $customCssContent = $this->twig->render('easyadmin.css.twig', array(
            'brand_color' => $backendConfig['design']['brand_color'],
            'color_scheme' => $backendConfig['design']['color_scheme'],
        ));

        // this avoids Symfony interpreting '%' used in CSS properties as container parameters
        $escapedCustomCssContent = str_replace('%', '%%', $customCssContent);

        $backendConfig['_internal']['custom_css'] = $escapedCustomCssContent;

        return $backendConfig;
    }
}
