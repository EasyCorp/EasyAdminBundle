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

use JavierEguiluz\Bundle\EasyAdminBundle\Configuration\ActionConfigPass;
use JavierEguiluz\Bundle\EasyAdminBundle\Configuration\DefaultConfigPass;
use JavierEguiluz\Bundle\EasyAdminBundle\Configuration\EntityConfigPass;
use JavierEguiluz\Bundle\EasyAdminBundle\Configuration\FormViewConfigPass;
use JavierEguiluz\Bundle\EasyAdminBundle\Configuration\PropertyConfigPass;
use JavierEguiluz\Bundle\EasyAdminBundle\Configuration\TemplateConfigPass;
use JavierEguiluz\Bundle\EasyAdminBundle\Configuration\ViewConfigPass;

/**
 * It applies several configuration normalizers in a row to transform any
 * input backend configuration into the normalized configuration used by
 * the rest of the code.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class ConfigurationNormalizer implements ConfigPassInterface
{
    /** @var ConfigPassInterface[] */
    private $configPasses;

    public function __construct($kernelRootDir)
    {
        $this->configPasses = array(
            new EntityConfigPass(),
            new FormViewConfigPass(),
            new ViewConfigPass(),
            new PropertyConfigPass(),
            new ActionConfigPass(),
            new TemplateConfigPass($kernelRootDir),
            new DefaultConfigPass(),
        );
    }

    public function process(array $backendConfiguration)
    {
        foreach ($this->configPasses as $configPass) {
            $backendConfiguration = $configPass->process($backendConfiguration);
        }

        return $backendConfiguration;
    }
}
