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

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('easy_admin');

        $rootNode
            ->children()
                ->scalarNode('site_name')
                    ->defaultValue('Easy Admin')
                    ->info('The name displayed as the title of the administration zone (e.g. company name, project name).')
                ->end()

                ->integerNode('list_max_results')
                    ->defaultValue(15)
                    ->info('The maximum number of items to show on listing and search pages.')
                ->end()

                ->variableNode('list_actions')
                    ->defaultValue(array('edit'))
                    ->info('The actions to show for each item of listing and search pages. Only "edit" and "show" options are available.')
                    ->example(array('edit', 'show'))
                ->end()

                ->arrayNode('assets')
                    ->performNoDeepMerging()
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('css')
                            ->prototype('scalar')->end()
                            ->info('The array of CSS assets to load in all backend pages.')
                        ->end()
                        ->arrayNode('js')
                            ->prototype('scalar')->end()
                            ->info('The array of JavaScript assets to load in all backend pages.')
                        ->end()
                    ->end()
                ->end()

                ->arrayNode('formats')
                    ->performNoDeepMerging()
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('date')
                            ->defaultValue('Y-m-d')
                            ->info('The PHP date format applied to "date" field types.')
                            ->example('d/m/Y (see http://php.net/manual/en/function.date.php)')
                        ->end()
                        ->scalarNode('time')
                            ->defaultValue('H:i:s')
                            ->info('The PHP time format applied to "time" field types.')
                            ->example('h:i a (see http://php.net/date)')
                        ->end()
                        ->scalarNode('datetime')
                            ->defaultValue('F j, Y H:i')
                            ->info('The PHP date/time format applied to "datetime" field types.')
                            ->example('l, F jS Y / h:i (see http://php.net/date)')
                        ->end()
                        ->scalarNode('number')
                            ->info('The sprintf-compatible format applied to numeric values.')
                            ->example('%.2d (see http://php.net/sprintf)')
                        ->end()
                    ->end()
                ->end()

                ->variableNode('entities')
                    ->defaultValue(array())
                    ->info('The list of entities to manage in the administration zone.')
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
