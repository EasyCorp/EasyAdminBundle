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
    private $twig;
    private $kernelDebug;

    public function __construct(\Twig_Environment $twig, $kernelDebug)
    {
        $this->kernelDebug = $kernelDebug;
        $this->twig = $twig;
    }

    public function process(array $backendConfig)
    {
        $backendConfig = $this->processCustomCss($backendConfig);

        return $backendConfig;
    }

    private function processCustomCss(array $backendConfig)
    {
        $customCssContent = $this->twig->render('@EasyAdmin/css/easyadmin.css.twig', array(
            'brand_color' => $backendConfig['design']['brand_color'],
            'color_scheme' => $backendConfig['design']['color_scheme'],
            'kernel_debug' => $this->kernelDebug,
        ));

        $minifiedCss = preg_replace(array('/\n/', '/\s{2,}/'), ' ', $customCssContent);
        $backendConfig['_internal']['custom_css'] = $minifiedCss;

        return $backendConfig;
    }
}
