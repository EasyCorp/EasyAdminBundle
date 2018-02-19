<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EasyCorp\Bundle\EasyAdminBundle\Configuration;

/**
 * Processes the template configuration to decide which template to use to
 * display each property in each view. It also processes the global templates
 * used when there is no entity configuration (e.g. for error pages).
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class TemplateConfigPass implements ConfigPassInterface
{
    private $twigLoader;
    private $defaultBackendTemplates = array(
        'layout' => '@EasyAdmin/default/layout.html.twig',
        'menu' => '@EasyAdmin/default/menu.html.twig',
        'edit' => '@EasyAdmin/default/edit.html.twig',
        'list' => '@EasyAdmin/default/list.html.twig',
        'new' => '@EasyAdmin/default/new.html.twig',
        'show' => '@EasyAdmin/default/show.html.twig',
        'exception' => '@EasyAdmin/default/exception.html.twig',
        'flash_messages' => '@EasyAdmin/default/flash_messages.html.twig',
        'paginator' => '@EasyAdmin/default/paginator.html.twig',
        'field_array' => '@EasyAdmin/default/field_array.html.twig',
        'field_association' => '@EasyAdmin/default/field_association.html.twig',
        'field_bigint' => '@EasyAdmin/default/field_bigint.html.twig',
        'field_boolean' => '@EasyAdmin/default/field_boolean.html.twig',
        'field_date' => '@EasyAdmin/default/field_date.html.twig',
        'field_datetime' => '@EasyAdmin/default/field_datetime.html.twig',
        'field_datetimetz' => '@EasyAdmin/default/field_datetimetz.html.twig',
        'field_decimal' => '@EasyAdmin/default/field_decimal.html.twig',
        'field_email' => '@EasyAdmin/default/field_email.html.twig',
        'field_file' => '@EasyAdmin/default/field_file.html.twig',
        'field_float' => '@EasyAdmin/default/field_float.html.twig',
        'field_guid' => '@EasyAdmin/default/field_guid.html.twig',
        'field_id' => '@EasyAdmin/default/field_id.html.twig',
        'field_image' => '@EasyAdmin/default/field_image.html.twig',
        'field_json' => '@EasyAdmin/default/field_json.html.twig',
        'field_json_array' => '@EasyAdmin/default/field_json_array.html.twig',
        'field_integer' => '@EasyAdmin/default/field_integer.html.twig',
        'field_object' => '@EasyAdmin/default/field_object.html.twig',
        'field_raw' => '@EasyAdmin/default/field_raw.html.twig',
        'field_simple_array' => '@EasyAdmin/default/field_simple_array.html.twig',
        'field_smallint' => '@EasyAdmin/default/field_smallint.html.twig',
        'field_string' => '@EasyAdmin/default/field_string.html.twig',
        'field_tel' => '@EasyAdmin/default/field_tel.html.twig',
        'field_text' => '@EasyAdmin/default/field_text.html.twig',
        'field_time' => '@EasyAdmin/default/field_time.html.twig',
        'field_toggle' => '@EasyAdmin/default/field_toggle.html.twig',
        'field_url' => '@EasyAdmin/default/field_url.html.twig',
        'label_empty' => '@EasyAdmin/default/label_empty.html.twig',
        'label_inaccessible' => '@EasyAdmin/default/label_inaccessible.html.twig',
        'label_null' => '@EasyAdmin/default/label_null.html.twig',
        'label_undefined' => '@EasyAdmin/default/label_undefined.html.twig',
    );

    public function __construct(\Twig_Loader_Filesystem $twigLoader)
    {
        $this->twigLoader = $twigLoader;
    }

    public function process(array $backendConfig)
    {
        $backendConfig = $this->processEntityTemplates($backendConfig);
        $backendConfig = $this->processDefaultTemplates($backendConfig);
        $backendConfig = $this->processFieldTemplates($backendConfig);

        return $backendConfig;
    }

    /**
     * Determines the template used to render each backend element. This is not
     * trivial because templates can depend on the entity displayed and they
     * define an advanced override mechanism.
     *
     * @param array $backendConfig
     *
     * @return array
     */
    private function processEntityTemplates(array $backendConfig)
    {
        // first, resolve the general template overriding mechanism
        // 1st level priority: easy_admin.entities.<entityName>.templates.<templateName> config option
        // 2nd level priority: easy_admin.design.templates.<templateName> config option
        // 3rd level priority: app/Resources/views/easy_admin/<entityName>/<templateName>.html.twig
        // 4th level priority: app/Resources/views/easy_admin/<templateName>.html.twig
        // 5th level priority: @EasyAdmin/default/<templateName>.html.twig
        foreach ($backendConfig['entities'] as $entityName => $entityConfig) {
            foreach ($this->defaultBackendTemplates as $templateName => $defaultTemplatePath) {
                $candidateTemplates = array(
                    isset($entityConfig['templates'][$templateName]) ? $entityConfig['templates'][$templateName] : null,
                    isset($backendConfig['design']['templates'][$templateName]) ? $backendConfig['design']['templates'][$templateName] : null,
                    'easy_admin/'.$entityName.'/'.$templateName.'.html.twig',
                    'easy_admin/'.$templateName.'.html.twig',
                );
                $templatePath = $this->findFirstExistingTemplate($candidateTemplates) ?: $defaultTemplatePath;

                if (null === $templatePath) {
                    throw new \RuntimeException(sprintf('None of the templates defined for the "%s" fragment of the "%s" entity exists (templates defined: %s).', $templateName, $entityName, implode(', ', $candidateTemplates)));
                }

                $entityConfig['templates'][$templateName] = $templatePath;
            }

            $backendConfig['entities'][$entityName] = $entityConfig;
        }

        // second, walk through all entity fields to determine their specific template
        foreach ($backendConfig['entities'] as $entityName => $entityConfig) {
            foreach (array('list', 'show') as $view) {
                foreach ($entityConfig[$view]['fields'] as $fieldName => $fieldMetadata) {
                    // if the field defines its own template, resolve its location
                    if (isset($fieldMetadata['template'])) {
                        $templatePath = $fieldMetadata['template'];

                        // template path should contain the .html.twig extension
                        // however, for usability reasons, we silently fix this issue if needed
                        if ('.html.twig' !== substr($templatePath, -10)) {
                            $templatePath .= '.html.twig';
                            @trigger_error(sprintf('Passing a template path without the ".html.twig" extension is deprecated since version 1.11.7 and will be removed in 2.0. Use "%s" as the value of the "template" option for the "%s" field in the "%s" view of the "%s" entity.', $templatePath, $fieldName, $view, $entityName), E_USER_DEPRECATED);
                        }

                        // before considering $templatePath a regular Symfony template
                        // path, check if the given template exists in any of these directories:
                        // * app/Resources/views/easy_admin/<entityName>/<templatePath>
                        // * app/Resources/views/easy_admin/<templatePath>
                        $templatePath = $this->findFirstExistingTemplate(array(
                            'easy_admin/'.$entityName.'/'.$templatePath,
                            'easy_admin/'.$templatePath,
                            $templatePath,
                        ));
                    } else {
                        // At this point, we don't know the exact data type associated with each field.
                        // The template is initialized to null and it will be resolved at runtime in the Configurator class
                        $templatePath = null;
                    }

                    $entityConfig[$view]['fields'][$fieldName]['template'] = $templatePath;
                }
            }

            $backendConfig['entities'][$entityName] = $entityConfig;
        }

        return $backendConfig;
    }

    /**
     * Determines the templates used to render each backend element when no
     * entity configuration is available. It's similar to processEntityTemplates()
     * but it doesn't take into account the details of each entity.
     * This is needed for example when an exception is triggered and no entity
     * configuration is available to know which template should be rendered.
     *
     * @param array $backendConfig
     *
     * @return array
     */
    private function processDefaultTemplates(array $backendConfig)
    {
        // 1st level priority: easy_admin.design.templates.<templateName> config option
        // 2nd level priority: app/Resources/views/easy_admin/<templateName>.html.twig
        // 3rd level priority: @EasyAdmin/default/<templateName>.html.twig
        foreach ($this->defaultBackendTemplates as $templateName => $defaultTemplatePath) {
            $candidateTemplates = array(
                isset($backendConfig['design']['templates'][$templateName]) ? $backendConfig['design']['templates'][$templateName] : null,
                'easy_admin/'.$templateName.'.html.twig',
            );
            $templatePath = $this->findFirstExistingTemplate($candidateTemplates) ?: $defaultTemplatePath;

            if (null === $templatePath) {
                throw new \RuntimeException(sprintf('None of the templates defined for the global "%s" template of the backend exists (templates defined: %s).', $templateName, implode(', ', $candidateTemplates)));
            }

            $backendConfig['design']['templates'][$templateName] = $templatePath;
        }

        return $backendConfig;
    }

    /**
     * Determines the template used to render each backend element. This is not
     * trivial because templates can depend on the entity displayed and they
     * define an advanced override mechanism.
     *
     * @param array $backendConfig
     *
     * @return array
     */
    private function processFieldTemplates(array $backendConfig)
    {
        foreach ($backendConfig['entities'] as $entityName => $entityConfig) {
            foreach (array('list', 'show') as $view) {
                foreach ($entityConfig[$view]['fields'] as $fieldName => $fieldMetadata) {
                    if (null !== $fieldMetadata['template']) {
                        continue;
                    }

                    // needed to add support for immutable datetime/date/time fields
                    // (which are rendered using the same templates as their non immutable counterparts)
                    if ('_immutable' === substr($fieldMetadata['dataType'], -10)) {
                        $fieldTemplateName = 'field_'.substr($fieldMetadata['dataType'], 0, -10);
                    } else {
                        $fieldTemplateName = 'field_'.$fieldMetadata['dataType'];
                    }

                    // primary key values are displayed unmodified to prevent common issues
                    // such as formatting its values as numbers (e.g. `1,234` instead of `1234`)
                    if ($entityConfig['primary_key_field_name'] === $fieldName) {
                        $template = $entityConfig['templates']['field_id'];
                    } elseif (array_key_exists($fieldTemplateName, $entityConfig['templates'])) {
                        $template = $entityConfig['templates'][$fieldTemplateName];
                    } else {
                        $template = $entityConfig['templates']['label_undefined'];
                    }

                    $entityConfig[$view]['fields'][$fieldName]['template'] = $template;
                }
            }

            $backendConfig['entities'][$entityName] = $entityConfig;
        }

        return $backendConfig;
    }

    private function findFirstExistingTemplate(array $templatePaths)
    {
        foreach ($templatePaths as $templatePath) {
            if (null !== $templatePath && $this->twigLoader->exists($templatePath)) {
                return $templatePath;
            }
        }
    }
}

class_alias('EasyCorp\Bundle\EasyAdminBundle\Configuration\TemplateConfigPass', 'JavierEguiluz\Bundle\EasyAdminBundle\Configuration\TemplateConfigPass', false);
