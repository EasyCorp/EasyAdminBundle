<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JavierEguiluz\Bundle\EasyAdminBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\Config\FileLocator;

/**
 * Resolves all the backend configuration values and most of the entities
 * configuration. The information that must resolved during runtime is handled
 * by the Configurator class.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class EasyAdminExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        // process bundle's configuration parameters
        $configs = $this->processConfigFiles($configs);
        $backendConfig = $this->processConfiguration(new Configuration(), $configs);
        $container->setParameter('easyadmin.config', $backendConfig);
        $container->setParameter('easyadmin.cache.dir', $container->getParameter('kernel.cache_dir').'/easy_admin');

        // load bundle's services
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');
        $loader->load('form.xml');

        // don't register our exception listener if debug is enabled
        if ($container->getParameter('kernel.debug')) {
            $container->removeDefinition('easyadmin.listener.exception');
        }

        // compile commonly used classes
        $this->addClassesToCompile(array(
            'JavierEguiluz\\Bundle\\EasyAdminBundle\\Configuration\\ConfigManager',
            'JavierEguiluz\\Bundle\\EasyAdminBundle\\Event\\EasyAdminEvents',
            'JavierEguiluz\\Bundle\\EasyAdminBundle\\EventListener\\ExceptionListener',
            'JavierEguiluz\\Bundle\\EasyAdminBundle\\EventListener\\RequestPostInitializeListener',
            'JavierEguiluz\\Bundle\\EasyAdminBundle\\Form\\Extension\EasyAdminExtension',
            'JavierEguiluz\\Bundle\\EasyAdminBundle\\Search\\Paginator',
            'JavierEguiluz\\Bundle\\EasyAdminBundle\\Search\\QueryBuilder',
            'JavierEguiluz\\Bundle\\EasyAdminBundle\\Twig\\EasyAdminTwigExtension',
        ));

        $this->ensureBackwardCompatibility($container);
    }

    /**
     * Makes some tweaks in order to ensure backward compatibilities
     * with supported versions of Symfony components.
     *
     * @param ContainerBuilder $container
     */
    private function ensureBackwardCompatibility(ContainerBuilder $container)
    {
        // BC for Symfony 2.3 and Request Stack
        $isRequestStackAvailable = class_exists('Symfony\\Component\\HttpFoundation\\RequestStack');
        if (!$isRequestStackAvailable) {
            $needsSetRequestMethodCall = array('easyadmin.listener.request_post_initialize', 'easyadmin.form.type.extension');
            foreach ($needsSetRequestMethodCall as $serviceId) {
                $container
                    ->getDefinition($serviceId)
                    ->addMethodCall('setRequest', array(
                        new Reference('request', ContainerInterface::NULL_ON_INVALID_REFERENCE, false),
                    ))
                ;
            }
        }

        // BC for legacy form component
        $useLegacyFormComponent = false === class_exists('Symfony\\Component\\Form\\Util\\StringUtil');
        if (!$useLegacyFormComponent) {
            $container
                ->getDefinition('easyadmin.form.type')
                ->clearTag('form.type')
                ->addTag('form.type')
            ;
        }
    }

    private function processConfigFiles(array $configs)
    {
        $existingEntityNames = array();

        foreach ($configs as $i => $config) {
            if (array_key_exists('entities', $config)) {
                $processedConfig = array();

                foreach ($config['entities'] as $key => $value) {
                    $entityConfig = $this->normalizeEntityConfig($key, $value);
                    $entityName = $this->getUniqueEntityName($key, $entityConfig, $existingEntityNames);
                    $entityConfig['name'] = $entityName;

                    $processedConfig[$entityName] = $entityConfig;

                    $existingEntityNames[] = $entityName;
                }

                $config['entities'] = $processedConfig;
            }

            $configs[$i] = $config;
        }

        return $configs;
    }

    /**
     * Transforms the two simple configuration formats into the full expanded
     * configuration. This allows to reuse the same method to process any of the
     * different configuration formats.
     *
     * These are the two simple formats allowed:
     *
     * # Config format #1: no custom entity name
     * easy_admin:
     *     entities:
     *         - AppBundle\Entity\User
     *
     * # Config format #2: simple config with custom entity name
     * easy_admin:
     *     entities:
     *         User: AppBundle\Entity\User
     *
     * And this is the full expanded configuration syntax generated by this method:
     *
     * # Config format #3: expanded entity configuration with 'class' parameter
     * easy_admin:
     *     entities:
     *         User:
     *             class: AppBundle\Entity\User
     *
     * @param mixed $entityName
     * @param mixed $entityConfig
     *
     * @return array
     */
    private function normalizeEntityConfig($entityName, $entityConfig)
    {
        // normalize config formats #1 and #2 to use the 'class' option as config format #3
        if (!is_array($entityConfig)) {
            $entityConfig = array('class' => $entityConfig);
        }

        // if config format #3 is used, ensure that it defines the 'class' option
        if (!isset($entityConfig['class'])) {
            throw new \RuntimeException(sprintf('The "%s" entity must define its associated Doctrine entity class using the "class" option.', $entityName));
        }

        return $entityConfig;
    }

    /**
     * The name of the entity is included in the URLs of the backend to define
     * the entity used to perform the operations. Obviously, the entity name
     * must be unique to identify entities unequivocally.
     *
     * This method ensures that the given entity name is unique among all the
     * previously existing entities passed as the second argument. This is
     * achieved by iteratively appending a suffix until the entity name is
     * guaranteed to be unique.
     *
     * @param string $entityName
     * @param array  $entityConfig
     * @param array  $existingEntityNames
     *
     * @return string The entity name transformed to be unique
     */
    private function getUniqueEntityName($entityName, array $entityConfig, array $existingEntityNames)
    {
        // the shortcut config syntax doesn't require to give entities a name
        if (is_numeric($entityName)) {
            $entityClassParts = explode('\\', $entityConfig['class']);
            $entityName = end($entityClassParts);
        }

        $i = 2;
        $uniqueName = $entityName;
        while (in_array($uniqueName, $existingEntityNames)) {
            $uniqueName = $entityName.($i++);
        }

        $entityName = $uniqueName;

        // make sure that the entity name is valid as a PHP method name
        // (this is required to allow extending the backend with a custom controller)
        if (!$this->isValidMethodName($entityName)) {
            throw new \InvalidArgumentException(sprintf('The name of the "%s" entity contains invalid characters (allowed: letters, numbers, underscores; the first character cannot be a number).', $entityName));
        }

        return $entityName;
    }

    /**
     * Checks whether the given string is valid as a PHP method name.
     *
     * @param string $name
     *
     * @return bool
     */
    private function isValidMethodName($name)
    {
        return 0 !== preg_match('/^-?[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $name);
    }
}
