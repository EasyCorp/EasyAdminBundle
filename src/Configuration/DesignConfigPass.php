<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Configuration;

/**
 * Processes the custom CSS styles applied to the backend design based on the
 * value of the design configuration options.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class DesignConfigPass implements ConfigPassInterface
{
    /** @var string */
    private $locale;

    public function __construct($locale)
    {
        $this->locale = $locale;
    }

    public function process(array $backendConfig)
    {
        $backendConfig = $this->processRtlLanguages($backendConfig);

        return $backendConfig;
    }

    private function processRtlLanguages(array $backendConfig)
    {
        if (!isset($backendConfig['design']['rtl'])) {
            // ar = Arabic, fa = Persian, he = Hebrew
            if (\in_array(mb_substr($this->locale, 0, 2), ['ar', 'fa', 'he'])) {
                $backendConfig['design']['rtl'] = true;
            } else {
                $backendConfig['design']['rtl'] = false;
            }
        }

        return $backendConfig;
    }
}
