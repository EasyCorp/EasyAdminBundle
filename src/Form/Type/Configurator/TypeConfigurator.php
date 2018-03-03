<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Type\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Configuration\ConfigManagerInterface;
use Symfony\Component\Form\FormConfigInterface;

/**
 * @author Konstantin Grachev <me@grachevko.ru>
 */
final class TypeConfigurator implements TypeConfiguratorInterface
{
    /**
     * @var ConfigManagerInterface
     */
    private $configManager;

    public function __construct(ConfigManagerInterface $configManager)
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
