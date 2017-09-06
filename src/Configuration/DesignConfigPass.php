<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EasyCorp\Bundle\EasyAdminBundle\Configuration;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Processes the custom CSS styles applied to the backend design based on the
 * value of the design configuration options.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class DesignConfigPass implements ConfigPassInterface
{
    /** @var ContainerInterface */
    private $container;
    /** @var bool */
    private $kernelDebug;
    /** @var string */
    private $locale;

    /**
     * @var ContainerInterface to prevent ServiceCircularReferenceException
     * @var bool               $kernelDebug
     * @var string             $locale
     */
    public function __construct(ContainerInterface $container, $kernelDebug, $locale)
    {
        $this->container = $container;
        $this->kernelDebug = $kernelDebug;
        $this->locale = $locale;
    }

    public function process(array $backendConfig)
    {
        $backendConfig = $this->processRtlLanguages($backendConfig);
        $backendConfig = $this->processCustomCss($backendConfig);

        return $backendConfig;
    }

    private function processRtlLanguages(array $backendConfig)
    {
        if (!isset($backendConfig['design']['rtl'])) {
            // ar = Arabic, fa = Persian, he = Hebrew
            if (in_array(substr($this->locale, 0, 2), array('ar', 'fa', 'he'))) {
                $backendConfig['design']['rtl'] = true;
            } else {
                $backendConfig['design']['rtl'] = false;
            }
        }

        return $backendConfig;
    }

    private function processCustomCss(array $backendConfig)
    {
        $customCssContent = $this->container->get('twig')->render('@EasyAdmin/css/easyadmin.css.twig', array(
            'brand_color' => $backendConfig['design']['brand_color'],
            'color_scheme' => $backendConfig['design']['color_scheme'],
            'kernel_debug' => $this->kernelDebug,
        ));

        $minifiedCss = preg_replace(array('/\n/', '/\s{2,}/'), ' ', $customCssContent);
        $backendConfig['_internal']['custom_css'] = $minifiedCss;

        return $backendConfig;
    }
}

class_alias('EasyCorp\Bundle\EasyAdminBundle\Configuration\DesignConfigPass', 'JavierEguiluz\Bundle\EasyAdminBundle\Configuration\DesignConfigPass', false);
