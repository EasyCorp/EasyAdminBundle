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

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('easy_admin');

        $this->addDeprecationsSection($rootNode);
        $this->addGlobalOptionsSection($rootNode);
        $this->addDesignSection($rootNode);
        $this->addViewsSection($rootNode);
        $this->addEntitiesSection($rootNode);

        return $treeBuilder;
    }

    private function addDeprecationsSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            // 'list_max_results' global option was deprecated in 1.0.8
            // and replaced by 'list -> max_results'
            ->beforeNormalization()
                ->ifTrue(function ($v) { return isset($v['list_max_results']); })
                ->then(function ($v) {
                    if (!isset($v['list'])) {
                        $v['list'] = array();
                    }

                    // if the new option is defined, don't override it with the legacy option
                    if (!isset($v['list']['max_results'])) {
                        $v['list']['max_results'] = $v['list_max_results'];
                    }

                    unset($v['list_max_results']);

                    return $v;
                })
            ->end()

            // 'list_actions' global option was deprecated in 1.0.8
            // and replaced by 'list -> actions'
            ->beforeNormalization()
                ->ifTrue(function ($v) { return isset($v['list_actions']); })
                ->then(function ($v) {
                    // if the new option is defined, don't override it with the legacy option
                    if (!isset($v['list']['actions'])) {
                        $v['list']['actions'] = $v['list_actions'];
                    }

                    unset($v['list_actions']);

                    return $v;
                })
            ->end()

            // make sure the new 'design' global option exists to simplify
            // updating the deprecated 'assets -> css' and 'assets -> js' options
            ->beforeNormalization()
                ->always()
                ->then(function ($v) {
                    if (!isset($v['design'])) {
                        $v['design'] = array('assets' => array());
                    }

                    return $v;
                })
            ->end()

            // 'assets -> css' global option was deprecated in 1.1.0
            // and replaced by 'design -> assets -> css'
            ->beforeNormalization()
                ->ifTrue(function ($v) { return isset($v['assets']['css']); })
                ->then(function ($v) {
                    // if the new option is defined, don't override it with the legacy option
                    if (!isset($v['design']['assets']['css'])) {
                        $v['design']['assets']['css'] = $v['assets']['css'];
                    }

                    unset($v['assets']['css']);

                    return $v;
                })
            ->end()

            // 'assets -> js' global option was deprecated in 1.1.0
            // and replaced by 'design -> assets -> js'
            ->beforeNormalization()
                ->ifTrue(function ($v) { return isset($v['assets']['js']); })
                ->then(function ($v) {
                    // if the new option is defined, don't override it with the legacy option
                    if (!isset($v['design']['assets']['js'])) {
                        $v['design']['assets']['js'] = $v['assets']['js'];
                    }

                    unset($v['assets']['js']);

                    return $v;
                })
            ->end()

            // after updating 'assets -> css' and 'assets -> js' deprecated options,
            // remove the parent 'assets' deprecated option if it's defined
            ->beforeNormalization()
                ->always()
                ->then(function ($v) {
                    if (isset($v['assets'])) {
                        unset($v['assets']);
                    }

                    return $v;
                })
            ->end()

            ->children()
                ->variableNode('list_actions')
                    ->info('DEPRECATED: use the "actions" option of the "list" view.')
                ->end()

                ->integerNode('list_max_results')
                    ->info('DEPRECATED: use "max_results" option under the "list" global key.')
                ->end()

                ->arrayNode('assets')
                    ->performNoDeepMerging()
                    ->children()
                        ->arrayNode('css')
                            ->info('DEPRECATED: use the "design -> assets -> css" option.')
                        ->end()
                        ->arrayNode('js')
                            ->info('DEPRECATED: use the "design -> assets -> js" option.')
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    private function addGlobalOptionsSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->scalarNode('site_name')
                    ->defaultValue('Easy Admin')
                    ->info('The name displayed as the title of the administration zone (e.g. company name, project name).')
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

                ->variableNode('disabled_actions')
                    ->info('The names of the actions disabled for all backend entities.')
                    ->defaultValue(array())
                    ->validate()
                        ->ifTrue(function ($v) { return false === is_array($v); })
                        ->thenInvalid('The disabled_actions option must be an array of action names.')
                    ->end()
                ->end()
            ->end()
        ;
    }

    private function addDesignSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('design')
                    ->performNoDeepMerging()
                    ->addDefaultsIfNotSet()
                    ->children()
                        // the 'theme' option is not used at the moment, but it allows us to be prepared for the future
                        ->scalarNode('theme')
                            ->defaultValue('default')
                            ->info('The theme used to render the backend pages. For now this value can only be "default".')
                            ->validate()
                                ->ifNotInArray(array('default'))
                                ->thenInvalid('The theme name can only be "default".')
                            ->end()
                        ->end()

                        ->enumNode('color_scheme')
                            ->values(array('dark', 'light'))
                            ->info('The color scheme applied to the backend design (values: "dark" or "light").')
                            ->defaultValue('dark')
                            ->treatNullLike('dark')
                        ->end()

                        ->scalarNode('brand_color')
                            ->info('The color used in the backend design to highlight important elements.')
                            ->defaultValue('#E67E22')
                            ->treatNullLike('#E67E22')
                            ->validate()
                                // if present, remove the trailing ';' to avoid CSS issues
                                ->ifTrue(function ($v) { return ';' === substr(trim($v), -1); })
                                ->then(function ($v) { return trim(substr(trim($v), 0, -1)); })
                            ->end()
                        ->end()

                        ->variableNode('form_theme')
                            ->defaultValue(array('@EasyAdmin/form/bootstrap_3_horizontal_layout.html.twig'))
                            ->treatNullLike(array('@EasyAdmin/form/bootstrap_3_horizontal_layout.html.twig'))
                            ->info('The form theme applied to backend forms. Allowed values: "horizontal", "vertical" and a custom theme path or array of custom theme paths.')
                            ->validate()
                                ->ifTrue(function ($v) { return 'horizontal' === $v; })
                                ->then(function () { return array('@EasyAdmin/form/bootstrap_3_horizontal_layout.html.twig'); })
                            ->end()
                            ->validate()
                                ->ifTrue(function ($v) { return 'vertical' === $v; })
                                ->then(function () { return array('@EasyAdmin/form/bootstrap_3_layout.html.twig'); })
                            ->end()
                            ->validate()
                                ->ifString()->then(function ($v) { return array($v); })
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

                        ->arrayNode('templates')
                            ->info('The custom templates used to render each backend element.')
                            ->performNoDeepMerging()
                            ->children()
                                ->scalarNode('layout')->info('Used to decorate the main templates (list, edit, new and show)')->end()
                                ->scalarNode('edit')->info('Used to render the page where entities are edited')->end()
                                ->scalarNode('list')->info('Used to render the listing page and the search results page')->end()
                                ->scalarNode('new')->info('Used to render the page where new entities are created')->end()
                                ->scalarNode('show')->info('Used to render the contents stored by a given entity')->end()
                                ->scalarNode('form')->info('Used to render the form displayed in the new and edit pages')->end()
                                ->scalarNode('paginator')->info('Used to render the paginator in the list page')->end()
                                ->scalarNode('field_array')->info('Used to render array field types')->end()
                                ->scalarNode('field_association')->info('Used to render fields that store Doctrine associations')->end()
                                ->scalarNode('field_bigint')->info('Used to render bigint field types')->end()
                                ->scalarNode('field_boolean')->info('Used to render boolean field types')->end()
                                ->scalarNode('field_date')->info('Used to render date field types')->end()
                                ->scalarNode('field_datetime')->info('Used to render datetime field types')->end()
                                ->scalarNode('field_datetimetz')->info('Used to render datetimetz field types')->end()
                                ->scalarNode('field_decimal')->info('Used to render decimal field types')->end()
                                ->scalarNode('field_float')->info('Used to render float field types')->end()
                                ->scalarNode('field_id')->info('Used to render the field called "id". This avoids formatting its value as any other regular number (with decimals and thousand separators) ')->end()
                                ->scalarNode('field_image')->info('Used to render image field types (a special type that displays the image contents)')->end()
                                ->scalarNode('field_integer')->info('Used to render integer field types')->end()
                                ->scalarNode('field_simple_array')->info('Used to render simple array field types')->end()
                                ->scalarNode('field_smallint')->info('Used to render smallint field types')->end()
                                ->scalarNode('field_string')->info('Used to render string field types')->end()
                                ->scalarNode('field_text')->info('Used to render text field types')->end()
                                ->scalarNode('field_time')->info('Used to render time field types')->end()
                                ->scalarNode('field_toggle')->info('Used to render toggle field types (a special type that display booleans as flip switches)')->end()
                                ->scalarNode('label_empty')->info('Used when the field to render is an empty collection')->end()
                                ->scalarNode('label_inaccessible')->info('Used when is not possible to access the value of the field to render (there is no getter or public property)')->end()
                                ->scalarNode('label_null')->info('Used when the value of the field to render is null')->end()
                                ->scalarNode('label_undefined')->info('Used when any kind of error or exception happens when trying to access the value of the field to render')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    private function addViewsSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
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
            ->end()
        ;
    }

    private function addEntitiesSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->variableNode('entities')
                    ->defaultValue(array())
                    ->info('The list of entities to manage in the administration zone.')
                ->end()
            ->end()
        ;
    }
}
