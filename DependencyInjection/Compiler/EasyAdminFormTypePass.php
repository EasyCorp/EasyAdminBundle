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

        $guessers = array_map(function ($guesserId) {
            return new Reference($guesserId);
        }, array_keys($container->findTaggedServiceIds('form.type_guesser')));
        $guesserChain = new Definition('Symfony\Component\Form\FormTypeGuesserChain', array($guessers));

        $formTypeDefinition->replaceArgument(2, $guesserChain);

        if (!$this->isLegacySymfonyForm()) {
            $formTypeDefinition->setTags(array('form.type' => array(
                array('alias' => 'JavierEguiluz\\Bundle\\EasyAdminBundle\\Form\\Type\\EasyAdminFormType')
            )));
        }
    }

    private function isLegacySymfonyForm()
    {
        return false === method_exists('JavierEguiluz\Bundle\EasyAdminBundle\Form\Type\EasyAdminFormType', 'getBlockPrefix');
    }
}
