<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JavierEguiluz\Bundle\EasyAdminBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @author Maxime Steinhausser <maxime.steinhausser@gmail.com>
 */
class EasyAdminFormTypePass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $formTypeDefinition = $container->getDefinition('easyadmin.form.type');

        $guesserIds = array_keys($container->findTaggedServiceIds('form.type_guesser'));
        $guessers = array_map(function ($id) { return new Reference($id); }, $guesserIds);
        $guesserChain = new Definition('Symfony\Component\Form\FormTypeGuesserChain', array($guessers));

        $formTypeDefinition->replaceArgument(2, $guesserChain);

        // Register the Ivory CKEditor type configurator only if the bundle
        // is installed and no default configuration is provided.
        if ($container->has('ivory_ck_editor.config_manager')
            && null === $container->get('ivory_ck_editor.config_manager')->getDefaultConfig()
        ) {
            $ckeditorConfigurator = $container->register(
                'easyadmin.form.type.configurator.ivory_ckeditor',
                'JavierEguiluz\\Bundle\\EasyAdminBundle\\Form\\Type\\Configurator\\IvoryCKEditorTypeConfigurator'
            );
            $container->getDefinition('easyadmin.form.type')->addMethodCall(
                'addTypeConfigurator',
                array($ckeditorConfigurator)
            );
        }
    }
}
