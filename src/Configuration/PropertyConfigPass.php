<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Configuration;

use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\FilterRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Form\Util\FormTypeHelper;
use Symfony\Component\Form\FormRegistryInterface;
use Symfony\Component\Form\FormTypeGuesserInterface;

/**
 * Processes the entity fields to complete their configuration and to treat
 * some fields in a special way.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class PropertyConfigPass implements ConfigPassInterface
{
    private $defaultEntityFieldConfig = [
        // CSS class or classes applied to form field or list/show property
        'css_class' => '',
        // date/time/datetime/number format applied to form field value
        'format' => null,
        // form field help message
        'help' => null,
        // form field label (if 'null', autogenerate it; if 'false', hide it)
        'label' => null,
        // its value matches the value of 'dataType' for list/show and the value of 'fieldType' for new/edit
        'type' => null,
        // Symfony form field type (text, date, number, choice, ...) used to display the field
        'fieldType' => null,
        // Data type (text, date, integer, boolean, ...) of the Doctrine property associated with the field
        'dataType' => null,
        // is a virtual field or a real Doctrine entity property?
        'virtual' => false,
        // listings can be sorted according to the values of this field
        'sortable' => true,
        // the path of the template used to render the field in 'show' and 'list' views
        'template' => null,
        // the options passed to the Symfony Form type used to render the form field
        'type_options' => [],
        // the name of the group where this form field is displayed (used only for complex form layouts)
        'form_group' => null,
        // the role or set of roles a user must have to see this property
        'permission' => null,
        'prepend_html' => null,
        'append_html' => null,
    ];

    private $defaultVirtualFieldMetadata = [
        'columnName' => 'virtual',
        'fieldName' => 'virtual',
        'id' => false,
        'length' => null,
        'nullable' => false,
        'precision' => 0,
        'scale' => 0,
        'sortable' => false,
        'type' => 'text',
        'type_options' => [
            'required' => false,
        ],
        'unique' => false,
        'virtual' => true,
    ];

    private $formRegistry;
    private $filterRegistry;

    public function __construct(FormRegistryInterface $formRegistry, FilterRegistry $filterRegistry)
    {
        $this->formRegistry = $formRegistry;
        $this->filterRegistry = $filterRegistry;
    }

    public function process(array $backendConfig)
    {
        $backendConfig = $this->processMetadataConfig($backendConfig);
        $backendConfig = $this->processFieldConfig($backendConfig);
        $backendConfig = $this->processFilterConfig($backendConfig);

        return $backendConfig;
    }

    /**
     * $entityConfig['properties'] stores the raw metadata provided by Doctrine.
     * This method adds some other options needed for EasyAdmin backends. This is
     * required because $entityConfig['properties'] will be used as the fields of
     * the views that don't define their fields.
     *
     * @param array $backendConfig
     *
     * @return array
     */
    private function processMetadataConfig(array $backendConfig)
    {
        /** @var FormTypeGuesserInterface $typeGuesser */
        $typeGuesser = $this->formRegistry->getTypeGuesser();

        foreach ($backendConfig['entities'] as $entityName => $entityConfig) {
            $properties = [];
            foreach ($entityConfig['properties'] as $propertyName => $propertyMetadata) {
                $typeGuess = $typeGuesser->guessType($entityConfig['class'], $propertyName);
                $requiredGuess = $typeGuesser->guessRequired($entityConfig['class'], $propertyName);

                $guessedType = null !== $typeGuess
                    ? FormTypeHelper::getTypeName($typeGuess->getType())
                    : $propertyMetadata['type'];

                $guessedTypeOptions = null !== $typeGuess
                    ? $typeGuess->getOptions()
                    : [];

                if (null !== $requiredGuess) {
                    $guessedTypeOptions['required'] = $requiredGuess->getValue();
                }

                $properties[$propertyName] = array_replace(
                    $this->defaultEntityFieldConfig,
                    $propertyMetadata,
                    [
                        'property' => $propertyName,
                        'dataType' => $propertyMetadata['type'],
                        'fieldType' => $guessedType,
                        'type_options' => $guessedTypeOptions,
                    ]
                );

                // 'boolean' properties are displayed by default as toggleable
                // flip switches (if the 'edit' action is enabled for the entity)
                if ('boolean' === $properties[$propertyName]['dataType'] && !\in_array('edit', $entityConfig['disabled_actions'], true)) {
                    $properties[$propertyName]['dataType'] = 'toggle';
                }
            }

            $backendConfig['entities'][$entityName]['properties'] = $properties;
        }

        return $backendConfig;
    }

    /**
     * Completes the configuration of each field/property with the metadata
     * provided by Doctrine for each entity property.
     *
     * @param array $backendConfig
     *
     * @return array
     */
    private function processFieldConfig(array $backendConfig)
    {
        foreach ($backendConfig['entities'] as $entityName => $entityConfig) {
            foreach (['edit', 'list', 'new', 'search', 'show'] as $view) {
                $originalViewConfig = $backendConfig['entities'][$entityName][$view];
                foreach ($entityConfig[$view]['fields'] as $fieldName => $fieldConfig) {
                    $originalFieldConfig = $originalViewConfig['fields'][$fieldName] ?? null;

                    if (\array_key_exists($fieldName, $entityConfig['properties'])) {
                        $fieldMetadata = array_merge(
                            $entityConfig['properties'][$fieldName],
                            ['virtual' => false]
                        );
                    } else {
                        // this is a virtual field which doesn't exist as a property of
                        // the related entity. That's why Doctrine can't provide metadata for it
                        $fieldMetadata = array_merge(
                            $this->defaultVirtualFieldMetadata,
                            ['columnName' => $fieldName, 'fieldName' => $fieldName]
                        );
                    }

                    $normalizedConfig = array_replace_recursive(
                        $this->defaultEntityFieldConfig,
                        $fieldMetadata,
                        $fieldConfig
                    );

                    // 'list', 'search' and 'show' views: use the value of the 'type' option
                    // as the 'dataType' option because the previous code has already
                    // prioritized end-user preferences over Doctrine and default values
                    if (\in_array($view, ['list', 'search', 'show'])) {
                        $normalizedConfig['dataType'] = $normalizedConfig['type'];
                    }

                    // 'new' and 'edit' views: if the user has defined the 'type' option
                    // for the field, use it as 'fieldType'. Otherwise, use the guessed
                    // form type of the property data type.
                    if (\in_array($view, ['edit', 'new'])) {
                        $normalizedConfig['fieldType'] = $originalFieldConfig['type'] ?? $normalizedConfig['fieldType'];

                        if (null === $normalizedConfig['fieldType']) {
                            // this is a virtual field which doesn't exist as a property of
                            // the related entity. Textarea is used as a default form type.
                            $normalizedConfig['fieldType'] = 'textarea';
                        }

                        $normalizedConfig['type_options'] = $this->getFormTypeOptionsOfProperty(
                            $normalizedConfig, $fieldMetadata, $originalFieldConfig
                        );

                        // EasyAdmin defined a 'help' option before Symfony did the same for form types
                        // Consider both of them equivalent and copy the 'type_options.help' into 'help'
                        // to ease further processing of config
                        if (isset($fieldConfig['help']) && isset($normalizedConfig['type_options']['help'])) {
                            throw new \RuntimeException(sprintf('The "%s" property in the "%s" view of the "%s" entity defines a help message using both the "help: ..." option from EasyAdmin and the "type_options: { help: ... }" option from Symfony Forms. These two options are equivalent, but you can only define one of them at the same time. Remove one of these two help messages.', $normalizedConfig['property'], $view, $entityName));
                        }

                        if (isset($normalizedConfig['type_options']['help']) && !isset($fieldConfig['help'])) {
                            $normalizedConfig['help'] = $normalizedConfig['type_options']['help'];
                        }

                        // process the 'prepend' and 'append' icons and contents that later
                        // are displayed as Bootstrap 'input groups'
                        $prependHtml = '';
                        if ($fieldConfig['prepend_icon'] ?? false) {
                            $prependHtml .= sprintf('<i class="fa fa-fw fa-%s"></i>', $fieldConfig['prepend_icon']);
                        }
                        if ($fieldConfig['prepend_content'] ?? false) {
                            $prependHtml .= sprintf('<span>%s</span>', $fieldConfig['prepend_content']);
                        }
                        $normalizedConfig['prepend_html'] = empty($prependHtml) ? null : $prependHtml;

                        $appendHtml = '';
                        if ($fieldConfig['append_icon'] ?? false) {
                            $appendHtml .= sprintf('<i class="fa fa-fw fa-%s"></i>', $fieldConfig['append_icon']);
                        }
                        if ($fieldConfig['append_content'] ?? false) {
                            $appendHtml .= sprintf('<span>%s</span>', $fieldConfig['append_content']);
                        }
                        $normalizedConfig['append_html'] = empty($appendHtml) ? null : $appendHtml;
                    }

                    // special case for the 'list' view: 'boolean' properties are displayed
                    // as toggleable flip switches when certain conditions are met
                    if ('list' === $view && 'boolean' === $normalizedConfig['dataType']) {
                        // conditions:
                        //   1) the end-user hasn't configured the field type explicitly
                        //   2) the 'edit' action is enabled for the 'list' view of this entity
                        if (!isset($originalFieldConfig['type']) && !\in_array('edit', $entityConfig['disabled_actions'], true)) {
                            $normalizedConfig['dataType'] = 'toggle';
                        }
                    }

                    if ('avatar' === $normalizedConfig['dataType'] && \in_array($view, ['list', 'search', 'show'])) {
                        // if the user didn't define a label explicitly, hide it but only in 'list' and 'search'
                        if (null === $normalizedConfig['label'] && \in_array($view, ['list', 'search'])) {
                            $normalizedConfig['label'] = false;
                        }

                        if (isset($normalizedConfig['height'])) {
                            $imageHeight = $normalizedConfig['height'];
                            $semanticHeights = ['sm' => 18, 'md' => 24, 'lg' => 48, 'xl' => 96];
                            if (!is_numeric($imageHeight) && !\array_key_exists($imageHeight, $semanticHeights)) {
                                throw new \InvalidArgumentException(sprintf('The "%s" property in the "%s" view of the "%s" entity defines an invalid value for the avatar "height" option. It must be either a numeric value (which represents the image height in pixels) or one of these semantic heights: "%s".', $normalizedConfig['fieldName'], $view, $entityName, implode(', ', array_keys($semanticHeights))));
                            }

                            $normalizedConfig['height'] = is_numeric($imageHeight) ? $imageHeight : $semanticHeights[$imageHeight];
                        } else {
                            $normalizedConfig['height'] = 'show' === $view ? 48 : 24;
                        }
                    }

                    if (null === $normalizedConfig['format']) {
                        $normalizedConfig['format'] = $this->getFieldFormat($normalizedConfig['type'], $backendConfig);
                    }

                    $backendConfig['entities'][$entityName][$view]['fields'][$fieldName] = $normalizedConfig;
                }
            }
        }

        return $backendConfig;
    }

    private function processFilterConfig(array $backendConfig): array
    {
        foreach ($backendConfig['entities'] as $entityName => $entityConfig) {
            foreach ($entityConfig['list']['filters'] ?? [] as $propertyName => $filterConfig) {
                $originalFilterConfig = $filterConfig;

                if (\array_key_exists($propertyName, $entityConfig['properties'])) {
                    // if the original filter didn't define the 'type' option, it will now
                    // be defined thanks to the 'type' value added by Doctrine's metadata
                    $filterConfig += $entityConfig['properties'][$propertyName];

                    if (!isset($originalFilterConfig['type'])) {
                        $guessedType = $this->filterRegistry->getTypeGuesser()
                            ->guessType($entityConfig['class'], $propertyName);

                        if (null !== $guessedType) {
                            $filterConfig['type'] = $guessedType->getType();
                            $filterConfig['type_options'] = array_replace_recursive($guessedType->getOptions(), $filterConfig['type_options']);
                        }
                    }
                } elseif ($filterConfig['mapped'] ?? true) {
                    throw new \InvalidArgumentException(sprintf('The "%s" filter configured in the "list" view of the "%s" entity refers to a property called "%s" which is not defined in that entity. Set the "mapped" option to false if you are defining a filter that is not related to a property of that entity (this is needed for example when filtering by a property of a different entity which is related via a Doctrine association).', $propertyName, $entityName, $propertyName));
                }

                if (!isset($filterConfig['type'])) {
                    throw new \InvalidArgumentException(sprintf('The "%s" filter defined in the "list" view of the "%s" entity must define its own "type" explicitly because EasyAdmin cannot autoconfigure it.', $propertyName, $entityName));
                }

                $backendConfig['entities'][$entityName]['list']['filters'][$propertyName] = $filterConfig;
            }
        }

        return $backendConfig;
    }

    /**
     * Resolves from type options of field.
     *
     * @param array $mergedConfig
     * @param array $guessedConfig
     * @param array $userDefinedConfig
     *
     * @return array
     */
    private function getFormTypeOptionsOfProperty(array $mergedConfig, array $guessedConfig, array $userDefinedConfig)
    {
        $resolvedFormOptions = $mergedConfig['type_options'];

        // if the user has defined a 'type', the type options
        // must be reset so they don't get mixed with the form components guess.
        // Only the 'required' and user defined option are kept
        if (
            isset($userDefinedConfig['type'], $guessedConfig['fieldType'])
            && $userDefinedConfig['type'] !== $guessedConfig['fieldType']
        ) {
            $resolvedFormOptions = array_merge(
                array_intersect_key($resolvedFormOptions, ['required' => null]),
                $userDefinedConfig['type_options'] ?? []
            );
        }
        // if the user has defined the "type" or "type_options"
        // AND the "type" is the same as the default one
        elseif (
            (
                isset($userDefinedConfig['type'])
                && isset($guessedConfig['fieldType'])
                && $userDefinedConfig['type'] === $guessedConfig['fieldType']
            ) || (
                !isset($userDefinedConfig['type']) && isset($userDefinedConfig['type_options'])
            )
        ) {
            $resolvedFormOptions = array_merge(
                $resolvedFormOptions,
                $userDefinedConfig['type_options'] ?? []
            );
        }

        return $resolvedFormOptions;
    }

    /**
     * Returns the date/time/datetime/number format for the given field
     * according to its type and the default formats defined for the backend.
     *
     * @param string $fieldType
     * @param array  $backendConfig
     *
     * @return string The format that should be applied to the field value
     */
    private function getFieldFormat($fieldType, array $backendConfig)
    {
        if (\in_array($fieldType, ['date', 'date_immutable', 'dateinterval', 'time', 'time_immutable', 'datetime', 'datetime_immutable', 'datetimetz'])) {
            // make 'datetimetz' use the same format as 'datetime'
            $fieldType = ('datetimetz' === $fieldType) ? 'datetime' : $fieldType;
            $fieldType = ('_immutable' === mb_substr($fieldType, -10)) ? mb_substr($fieldType, 0, -10) : $fieldType;

            return $backendConfig['formats'][$fieldType];
        }

        if (\in_array($fieldType, ['bigint', 'integer', 'smallint', 'decimal', 'float'])) {
            return $backendConfig['formats']['number'] ?? null;
        }
    }
}
