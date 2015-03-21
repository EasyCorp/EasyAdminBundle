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
            // 'list_actions' and 'max_results' global options are deprecated since 1.0.8
            // and they are replaced by 'actions' and 'list -> max_results' options
            ->validate()
                ->ifTrue(function ($v) { return isset($v['list_max_results']); })
                ->then(function ($v) {
                    if (!isset($v['list'])) {
                        $v['list'] = array();
                    }

                    $v['list']['max_results'] = $v['list_max_results'];
                    unset($v['list_max_results']);

                    return $v;
                })
            ->end()
            ->validate()
                ->ifTrue(function ($v) { return isset($v['list_actions']); })
                ->then(function ($v) {
                    $v['list']['actions'] = $v['list_actions'];
                    unset($v['list_actions']);

                    return $v;
                })
            ->end()
            ->children()
                ->scalarNode('site_name')
                    ->defaultValue('Easy Admin')
                    ->info('The name displayed as the title of the administration zone (e.g. company name, project name).')
                ->end()

                ->variableNode('list_actions')
                    ->defaultNull()
                    ->info('DEPRECATED: use the "actions" option of the "list" view.')
                ->end()

                ->integerNode('list_max_results')
                    ->defaultNull()
                    ->info('DEPRECATED: use "max_results" option under the "list" global key.')
                ->end()

                ->arrayNode('list')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('actions')
                            ->prototype('variable')->end()
                            ->info('The list of actions enabled in the "list" view.')
                        ->end()
                        ->integerNode('max_results')
                            ->defaultValue(15)
                            ->info('The maximum number of items to show on listing and search pages.')
                        ->end()
                    ->end()
                ->end()

                ->arrayNode('edit')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('actions')
                            ->prototype('variable')->end()
                            ->info('The list of actions enabled in the "edit" view.')
                        ->end()
                    ->end()
                ->end()

                ->arrayNode('new')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('actions')
                            ->prototype('variable')->end()
                            ->info('The list of actions enabled in the "new" view.')
                        ->end()
                    ->end()
                ->end()

                ->arrayNode('show')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('actions')
                            ->prototype('variable')->end()
                            ->info('The list of actions enabled in the "show" view.')
                        ->end()
                    ->end()
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
