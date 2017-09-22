<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Type\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Configuration\ConfigManager;
use Symfony\Component\Form\FormConfigInterface;

/**
 * @author Konstantin Grachev <me@grachevko.ru>
 */
final class TypeConfigurator implements TypeConfiguratorInterface
{
    /**
     * @var ConfigManager
     */
    private $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function configure($name, array $options, array $metadata, FormConfigInterface $parentConfig)
    {
        if (!array_key_exists('label', $options) && array_key_exists('label', $metadata)) {
            $options['label'] = $metadata['label'];
        }

        if (empty($options['translation_domain'])) {
            $entityConfig = $this->configManager->getEntityConfig($parentConfig->getOption('entity'));

            if (!empty($entityConfig['translation_domain'])) {
                $options['translation_domain'] = $entityConfig['translation_domain'];
            }
        }

        return $options;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($type, array $options, array $metadata)
    {
        return true;
    }
}
