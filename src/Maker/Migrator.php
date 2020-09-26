<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Maker;

use Doctrine\DBAL\Types\Types;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\UserMenu;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AvatarField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CodeEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ColorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CountryField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ArrayFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ComparisonFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\ArrayFilterType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\BooleanFilterType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\ChoiceFilterType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\ComparisonFilterType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\DateTimeFilterType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\EntityFilterType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\NumericFilterType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\TextFilterType;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Security\Core\User\UserInterface;
use function Symfony\Component\String\u;

final class Migrator
{
    private $ea2Config;
    private $outputDir;
    private $namespace;
    private $output;
    private $fs;
    private $parser;
    private $codePrettyPrinter;

    public function migrate(array $ea2Config, string $outputDir, string $namespace, OutputInterface $output): void
    {
        $this->ea2Config = $ea2Config;
        $this->outputDir = $outputDir;
        $this->namespace = $namespace;
        $this->output = $output;
        $this->fs = new Filesystem();
        $this->parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $this->codePrettyPrinter = new Standard();

        $generatedFilesFqcn = $this->generateCrudControllers();
        $this->generateDashboardController($generatedFilesFqcn);
    }

    private function generateCrudControllers(): array
    {
        $generatedEntitiesFqcn = [];

        foreach ($this->ea2Config['entities'] as $entityName => $entityConfig) {
            $entityFqcn = $entityConfig['class'];
            $entityClassName = u($entityFqcn)->afterLast('\\')->toString();
            $crudControllerClassName = sprintf('%sCrudController', u($entityName)->replace(' ', '')->camel()->title());

            $code = CodeBuilder::new()
                ->_namespace($this->namespace)
                ->_use(AbstractCrudController::class)
                ->_use($entityFqcn)
                ->_class($crudControllerClassName)->_extends('AbstractCrudController')
                ->openBrace()
                    ->_public()->_static()->_function()->_method('getEntityFqcn', [], 'string')
                    ->openBrace()
                        ->_return()->_variableValue($entityClassName.'::class')->semiColon()
                    ->closeBrace();

            $code = $this->addConfigureCrudMethod($code, $entityClassName, $entityConfig);
            $code = $this->addConfigureActionsMethod($code, $entityConfig);
            $code = $this->addConfigureFiltersMethod($code, $entityConfig);
            $code = $this->addConfigureFieldsMethod($code, $entityConfig);

            $code = $code->closeBrace(); // closing for 'class ... {'

            $controllerClassName = $crudControllerClassName.'.php';
            $outputFilePath = $this->outputDir.'/'.$controllerClassName;
            $isDumped = $this->dumpCode($code, $outputFilePath);

            if ($isDumped) {
                $this->output->write(sprintf(' // Generated %s', $controllerClassName));
                $generatedEntitiesFqcn[] = $entityFqcn;
            }
        }

        return $generatedEntitiesFqcn;
    }

    private function generateDashboardController(array $entitiesFqcn): void
    {
        $code = CodeBuilder::new()
            ->_namespace($this->namespace)
            ->_use(AbstractDashboardController::class)
            ->_class('DashboardController')->_extends('AbstractDashboardController')
            ->openBrace();
        //->_raw('/** * @Route("/admin", name="admin") */ public function index(): Response { return parent::index(); }')

        $code = $this->addConfigureDashboardMethodInDashboardController($code, $this->ea2Config);
        $code = $this->addConfigureCrudMethodInDashboardController($code, $this->ea2Config);
        $code = $this->addConfigureUserMenuMethodInDashboardController($code, $this->ea2Config);
        $code = $this->addConfigureMenuItemsMethodInDashboardController($code, $this->ea2Config, $entitiesFqcn);

        $code = $code->closeBrace();

        $controllerClassName = 'DashboardController.php';
        $outputFilePath = $this->outputDir.'/'.$controllerClassName;
        $isDumped = $this->dumpCode($code, $outputFilePath);

        if ($isDumped) {
            $this->output->write(sprintf(' // Generated %s', $controllerClassName));
        }
    }

    private function addConfigureCrudMethod(CodeBuilder $code, string $entityClassName, array $entityConfig): CodeBuilder
    {
        $customLabel = $entityConfig['label'];
        $definesCustomLabel = $entityClassName === $customLabel;

        $definesCustomTemplates = 0 !== array_sum(array_map(function ($templatePath) {
            return $this->isCustomTemplate($templatePath) ? 0 : 1;
        }, $entityConfig['templates']));

        $definesCustomHelp = null !== $entityConfig['list']['help']
            || null !== $entityConfig['edit']['help']
            || null !== $entityConfig['new']['help']
            || null !== $entityConfig['show']['help'];

        $definesCustomPagination = 15 !== $entityConfig['list']['max_results'];
        $definesCustomEntityPermission = null !== $entityConfig['list']['item_permission'];
        $definesCustomActionDropdown = true === $entityConfig['list']['collapse_actions'];

        $definesCustomSearchFields = isset($entityConfig['search']['fields']);

        $customTitles = [];
        foreach (['list', 'edit', 'new', 'show'] as $oldPageName) {
            $customTitles[$oldPageName] = trim($entityConfig[$oldPageName]['title'] ?? '');
        }
        $definesCustomPageTitles = 0 !== array_sum(array_map('strlen', $customTitles));

        if (!$definesCustomLabel && !$definesCustomTemplates && !$definesCustomHelp && !$definesCustomPagination && !$definesCustomEntityPermission && !$definesCustomActionDropdown && !$definesCustomSearchFields && !$definesCustomPageTitles) {
            return $code;
        }

        $code = $code
            ->_use(Crud::class)
            ->_public()->_function()->_method('configureCrud', ['Crud $crud'], 'Crud')
            ->openBrace()
            ->_return()->_variableName('crud');

        if ($definesCustomLabel) {
            $code = $code
                ->_methodCall('setEntityLabelInSingular', [$customLabel])
                ->_methodCall('setEntityLabelInPlural', [$customLabel]);
        }

        if ($definesCustomPageTitles) {
            foreach (['list' => 'PAGE_INDEX', 'edit' => 'PAGE_EDIT', 'show' => 'PAGE_DETAIL', 'new' => 'PAGE_NEW'] as $oldPageName => $newPageName) {
                $pageTitle = $customTitles[$oldPageName];
                if (!empty($pageTitle)) {
                    $newPageTitle = str_replace(
                        ['%entity_label%', '%entity_id%'],
                        ['%entity_label_singular%', '%entity_short_id%'],
                        $pageTitle
                    );

                    $code = $code->_methodCallWithRawArguments('setPageTitle', ['Crud::'.$newPageName, "'".$newPageTitle."'"]);
                }
            }
        }

        if ($definesCustomHelp) {
            foreach (['list' => 'index', 'edit' => 'edit', 'show' => 'detail', 'new' => 'new'] as $oldPageName => $newPageName) {
                if (null !== $helpMessage = $entityConfig[$oldPageName]['help']) {
                    $code = $code->_methodCall('setHelp', [$newPageName, $helpMessage]);
                }
            }
        }

        if ($definesCustomSearchFields) {
            $searchFieldNames = array_keys($entityConfig['search']['fields']);
            $searchFieldVariables = array_map(static function ($searchFieldName) {
                return sprintf('\'%s\'', $searchFieldName);
            }, $searchFieldNames);
            $searchFieldNamesAsString = sprintf('[%s]', implode(', ', $searchFieldVariables));

            $code = $code->_methodCallWithRawArguments('setSearchFields', [$searchFieldNamesAsString]);
        }

        if ($definesCustomPagination) {
            $code = $code
                ->_methodCall('setPaginatorPageSize', [$entityConfig['list']['max_results']]);
        }

        if ($definesCustomActionDropdown) {
            $code = $code->_methodCall('showEntityActionsAsDropdown');
        }

        if ($definesCustomEntityPermission) {
            $code = $code
                ->_methodCall('setEntityPermission', [$entityConfig['list']['item_permission']]);
        }

        if ($definesCustomTemplates) {
            foreach ($entityConfig['templates'] as $oldTemplateName => $templatePath) {
                if (!$this->isCustomTemplate($templatePath)) {
                    continue;
                }

                $newTemplateName = $this->getNewTemplateNameFromOldTemplateName($oldTemplateName);
                $code = $code->_methodCall('overrideTemplate', [$newTemplateName, $templatePath]);
            }
        }

        $code = $code
            ->semiColon()
            ->closeBrace();

        return $code;
    }

    private function addConfigureActionsMethod(CodeBuilder $code, array $entityConfig): CodeBuilder
    {
        if (empty($entityConfig['disabled_actions'])) {
            return $code;
        }

        $code = $code
            ->_use(Actions::class)
            ->_public()->_function()->_method('configureActions', ['Actions $actions'], 'Actions')
            ->openBrace()
            ->_return()->_variableName('actions')
            ->_methodCall('disable', $entityConfig['disabled_actions'])
            ->semiColon()
            ->closeBrace();

        return $code;
    }

    private function addConfigureFieldsMethod(CodeBuilder $code, array $entityConfig): CodeBuilder
    {
        $code = $code
            ->_public()->_function()->_method('configureFields', ['string $pageName'], 'iterable')
            ->openBrace();

        $configuredFields = array_merge(
            $entityConfig['new']['fields'],
            $entityConfig['edit']['fields'],
            $entityConfig['show']['fields'],
            $entityConfig['list']['fields']
        );

        $numOfPanel = 0;
        $renamedFieldNames = [];
        foreach ($configuredFields as $fieldName => $fieldConfig) {
            // only "groups" can be migrated to EA3 (which calls them "panels")
            $isFormDesignElement = u($fieldName)->startsWith('_easyadmin_form_design_element');
            $isFormPanel = 'easyadmin_group' === $fieldConfig['type'];

            if ($isFormDesignElement) {
                if (!$isFormPanel) {
                    continue;
                }

                $newFieldName = sprintf('panel%d', ++$numOfPanel);
                $renamedFieldNames[$fieldName] = $newFieldName;

                $methodArguments = [];
                if (null !== $fieldLabel = $fieldConfig['label']) {
                    $methodArguments[] = $fieldLabel;
                }

                $code = $code
                    ->_use(FormField::class)
                    ->_variableName($newFieldName)->equals()->_staticCall('FormField', 'addPanel', $methodArguments)->semiColon();

                continue;
            }

            $fieldFqcn = $this->guessFieldFqcnForProperty($fieldConfig);
            $fieldClassName = u($fieldFqcn)->afterLast('\\')->toString();

            $methodArguments = [$fieldName];
            $fieldLabel = $fieldConfig['label'];
            $humanizedLabel = null === $fieldLabel ? null : $this->humanizeString($fieldLabel);
            // in EA2, an empty label means no label (same as FALSE in EA3)
            if ('' === $fieldLabel) {
                $methodArguments[] = false;
            } elseif ($fieldLabel !== $humanizedLabel) {
                // to keep config more concise, set the label explicitly only if
                // it's different from the autogenerated label
                $methodArguments[] = $fieldLabel;
            }

            // needed to turn 'foo' into '$foo' and 'foo.bar.baz' into '$fooBarBaz'
            $fieldVariableName = u($fieldName)->replace('.', ' ')->camel()->collapseWhitespace()->toString();
            $renamedFieldNames[$fieldName] = $fieldVariableName;

            $code = $code->_use($fieldFqcn);
            $code = $code->_variableName($fieldVariableName)->equals()->_staticCall($fieldClassName, 'new', $methodArguments);

            if ($this->isCustomTemplate($fieldConfig['template'])) {
                $code = $code->_methodCall('setTemplatePath', [$fieldConfig['template']]);
            }

            if (!empty($this->processCssClass($fieldConfig['css_class']))) {
                $code = $code->_methodCall('addCssClass', [trim($fieldConfig['css_class'])]);
            }

            if (!empty($fieldConfig['help'])) {
                $code = $code->_methodCall('setHelp', [$fieldConfig['help']]);
            }

            // TODO: reenable when it works better
            //if (!empty($fieldConfig['type_options'])) {
            //    $code = $code->_methodCall('setFormTypeOptions', [$fieldConfig['type_options']]);
            //}

            if (null !== $fieldConfig['permission']) {
                $code = $code->_methodCall('setPermission', [$fieldConfig['permission']]);
            }

            if ('toggle' === $fieldConfig['type'] && BooleanField::class === $fieldFqcn) {
                $code = $code->_methodCall('renderAsSwitch');
            }

            $code = $code->semiColon();
        }

        $indexVariableNames = $this->processAndFilterFieldNames($entityConfig['list']['fields'], $renamedFieldNames);
        $detailVariableNames = $this->processAndFilterFieldNames($entityConfig['show']['fields'], $renamedFieldNames);
        $newVariableNames = $this->processAndFilterFieldNames($entityConfig['new']['fields'], $renamedFieldNames);
        $editVariableNames = $this->processAndFilterFieldNames($entityConfig['edit']['fields'], $renamedFieldNames);

        $code = $code
            ->_use(Crud::class)
            ->_if('Crud::PAGE_INDEX === $pageName')->openBrace()
                ->_returnArrayOfVariables($indexVariableNames)->semiColon()
            ->closeBrace()
            ->_elseif('Crud::PAGE_DETAIL === $pageName')->openBrace()
                ->_returnArrayOfVariables($detailVariableNames)->semiColon()
            ->closeBrace()
            ->_elseif('Crud::PAGE_NEW === $pageName')->openBrace()
                ->_returnArrayOfVariables($newVariableNames)->semiColon()
            ->closeBrace()
            ->_elseif('Crud::PAGE_EDIT === $pageName')->openBrace()
                ->_returnArrayOfVariables($editVariableNames)->semiColon()
            ->closeBrace();

        $code = $code->closeBrace();

        return $code;
    }

    private function addConfigureFiltersMethod(CodeBuilder $code, array $entityConfig): CodeBuilder
    {
        if (empty($entityConfig['list']['filters'])) {
            return $code;
        }

        $code = $code
            ->_use(Filters::class)
            ->_public()->_function()->_method('configureFilters', ['Filters $filters'], 'Filters')
            ->openBrace()
            ->newLine()
            ->_return()->_variableName('filters');

        foreach ($entityConfig['list']['filters'] as $propertyName => $filterConfig) {
            [$filterFqcn, $filterCanBeGuessed] = $this->guessFilterFqcnForProperty($filterConfig);

            $filterClassName = u($filterFqcn)->afterLast('\\')->toString();
            $filterMethodArguments = [];
            $filterMethodArguments[] = "'".$propertyName."'";

            $filterLabel = $filterConfig['label'];
            $humanizedLabel = null === $filterLabel ? null : $this->humanizeString($filterLabel);
            $labelCanBeGuessed = $filterLabel === $humanizedLabel;
            // in EA2, an empty label means no label (same as FALSE in EA3)
            if ('' === $filterLabel) {
                $filterMethodArguments[] = false;
            } elseif (!$labelCanBeGuessed) {
                // to keep config more concise, set the label explicitly only if
                // it's different from the autogenerated label
                $filterMethodArguments[] = "'".$filterLabel."'";
            }

            if ($filterCanBeGuessed && $labelCanBeGuessed) {
                $code = $code->_methodCall('add', [$propertyName]);

                continue;
            }

            $methodArguments = [sprintf('%s::new(%s)', $filterClassName, implode(', ', $filterMethodArguments))];

            $code = $code
                ->_use($filterFqcn)
                ->_methodCallWithRawArguments('add', $methodArguments);
        }

        $code->semiColon()->newLine()->closeBrace();

        return $code;
    }

    private function getNewTemplateNameFromOldTemplateName(string $oldTemplateName): string
    {
        return [
                'layout' => 'layout',
                'menu' => 'menu',
                'edit' => 'crud/edit',
                'list' => 'crud/index',
                'new' => 'crud/new',
                'show' => 'crud/detail',
                'action' => 'crud/action',
                'filters' => 'crud/filters',
                'exception' => 'exception',
                'flash_messages' => 'flash_messages',
                'paginator' => 'crud/paginator',
                'field_array' => 'crud/field/array',
                'field_association' => 'crud/field/association',
                'field_avatar' => 'crud/field/avatar',
                //'field_bigint' => 'crud/field/bigint',
                'field_boolean' => 'crud/field/boolean',
                'field_country' => 'crud/field/country',
                'field_date' => 'crud/field/date',
                //'field_dateinterval' => 'crud/field/dateinterval',
                'field_datetime' => 'crud/field/datetime',
                //'field_datetimetz' => 'crud/field/datetimetz',
                'field_decimal' => 'crud/field/number',
                'field_email' => 'crud/field/email',
                //'field_file' => 'crud/field/file',
                'field_float' => 'crud/field/number',
                //'field_guid' => 'crud/field/guid',
                'field_id' => 'crud/field/id',
                'field_image' => 'crud/field/image',
                //'field_json' => 'crud/field/json',
                'field_json_array' => 'crud/field/array',
                'field_integer' => 'crud/field/integer',
                //'field_object' => 'crud/field/object',
                'field_percent' => 'crud/field/percent',
                //'field_raw' => 'crud/field/raw',
                'field_simple_array' => 'crud/field/array',
                //'field_smallint' => 'crud/field/smallint',
                'field_string' => 'crud/field/text',
                'field_tel' => 'crud/field/telephone',
                'field_text' => 'crud/field/text',
                'field_time' => 'crud/field/time',
                'field_toggle' => 'crud/field/boolean',
                'field_url' => 'crud/field/url',
                'label_empty' => 'label/empty',
                'label_inaccessible' => 'label/inaccessible',
                'label_null' => 'label/null',
                'label_undefined' => 'label/undefined',
            ][$oldTemplateName] ?? $oldTemplateName;
    }

    private function guessFieldFqcnForProperty(array $fieldConfig): string
    {
        $fieldType = $fieldConfig['type'];
        $doctrineDataType = $fieldConfig['dataType'];

        $fieldTypeToFqcn = [
            'easyadmin_group' => FormField::class,
            'association' => AssociationField::class,
            'avatar' => AvatarField::class,
            'code_editor' => CodeEditorField::class,
            'color' => ColorField::class,
            'country' => CountryField::class,
            'image' => ImageField::class,
            'email' => EmailField::class,
            'tel' => TelephoneField::class,
            'url' => UrlField::class,
        ];

        $doctrineTypeToFqcn = [
            Types::ARRAY => ArrayField::class,
            Types::BIGINT => TextField::class,
            Types::BINARY => TextareaField::class,
            Types::BLOB => TextareaField::class,
            Types::BOOLEAN => BooleanField::class,
            Types::DATE_MUTABLE => DateField::class,
            Types::DATE_IMMUTABLE => DateField::class,
            Types::DATEINTERVAL => TextField::class,
            Types::DATETIME_MUTABLE => DateTimeField::class,
            Types::DATETIME_IMMUTABLE => DateTimeField::class,
            Types::DATETIMETZ_MUTABLE => DateTimeField::class,
            Types::DATETIMETZ_IMMUTABLE => DateTimeField::class,
            Types::DECIMAL => NumberField::class,
            Types::FLOAT => NumberField::class,
            Types::GUID => TextField::class,
            Types::INTEGER => IntegerField::class,
            Types::JSON => TextField::class,
            // 'json_array' is now deprecated in favor of Types::JSON
            'json_array' => TextField::class,
            Types::OBJECT => TextField::class,
            Types::SIMPLE_ARRAY => ArrayField::class,
            Types::SMALLINT => IntegerField::class,
            Types::STRING => TextField::class,
            Types::TEXT => TextareaField::class,
            Types::TIME_MUTABLE => TimeField::class,
            Types::TIME_IMMUTABLE => TimeField::class,
        ];

        return $fieldTypeToFqcn[$fieldType] ?? $doctrineTypeToFqcn[$doctrineDataType] ?? Field::class;
    }

    private function guessFilterFqcnForProperty(array $filterConfig): array
    {
        $configuredFilterType = $filterConfig['type'];
        $propertyType = $filterConfig['dataType'];

        // if the filter form type defined in the filter config is the same that it's
        // guessed for the property, the generated code can be more concise
        // (e.g. for a text property called 'name', generate ->add('name') instead of
        // ->add(TextFilter::new('name')), which is equivalent but longer and EasyAdmin can guess it)
        $propertyTypeToDefaultFilterType = [
            'array' => ArrayFilterType::class,
            'simple_array' => ArrayFilterType::class,
            'boolean' => BooleanFilterType::class,
            'toggle' => BooleanFilterType::class,
            'text' => TextFilterType::class,
            'string' => TextFilterType::class,
            'float' => NumericFilterType::class,
            'integer' => NumericFilterType::class,
            'decimal' => NumericFilterType::class,
            'date' => DateTimeFilterType::class,
            'time' => DateTimeFilterType::class,
            'datetime' => DateTimeFilterType::class,
        ];

        $filterTypeToFqcn = [
            ArrayFilterType::class => ArrayFilter::class,
            BooleanFilterType::class => BooleanFilter::class,
            ChoiceFilterType::class => ChoiceFilter::class,
            ComparisonFilterType::class => ComparisonFilter::class,
            DateTimeFilterType::class => DateTimeFilter::class,
            EntityFilterType::class => EntityFilter::class,
            NumericFilterType::class => NumericFilter::class,
            TextFilterType::class => TextFilter::class,
        ];

        $guessedFilterType = $propertyTypeToDefaultFilterType[$propertyType] ?? null;

        return [
            $filterTypeToFqcn[$configuredFilterType] ?? TextFilter::class,
            $guessedFilterType === $configuredFilterType,
        ];
    }

    private function isCustomTemplate(?string $templatePath): bool
    {
        if (null === $templatePath) {
            return false;
        }

        return !u($templatePath)->startsWith('@EasyAdmin/');
    }

    private function humanizeString(string $string): string
    {
        return ucfirst(mb_strtolower(trim(preg_replace(['/([A-Z])/', '/[_\s]+/'], ['_$1', ' '], $string))));
    }

    private function addConfigureDashboardMethodInDashboardController(CodeBuilder $code, array $ea2Config): CodeBuilder
    {
        $code = $code
            ->_use(Dashboard::class)
            ->_public()->_function()->_method('configureDashboard', [], 'Dashboard')
            ->openBrace()
                ->_return()->_staticCall('Dashboard', 'new')
                    ->_methodCall('setTitle', [$ea2Config['site_name']]);

        if ('messages' !== $ea2Config['translation_domain']) {
            $code = $code->_methodCall('setTranslationDomain', [$ea2Config['translation_domain']]);
        }

        $code = $code
            ->semiColon()
            ->closeBrace();

        return $code;
    }

    private function addConfigureCrudMethodInDashboardController(CodeBuilder $code, array $ea2Config): CodeBuilder
    {
        $customDateFormat = $ea2Config['formats']['date'];
        $definesCustomDateFormat = 'Y-m-d' !== $customDateFormat;

        $customDateTimeFormat = $ea2Config['formats']['datetime'];
        $definesCustomDateTimeFormat = 'F j, Y H:i' !== $customDateTimeFormat;

        $customDateIntervalFormat = $ea2Config['formats']['dateinterval'];
        $definesCustomDateIntervalFormat = '%y Year(s) %m Month(s) %d Day(s)' !== $customDateIntervalFormat;

        $customTimeFormat = $ea2Config['formats']['time'];
        $definesCustomTimeFormat = 'H:i:s' !== $customTimeFormat;

        $definesCustomTemplates = 0 !== array_sum(array_map(function ($templatePath) {
            return $this->isCustomTemplate($templatePath) ? 0 : 1;
        }, $ea2Config['design']['templates']));

        if (!$definesCustomDateFormat && !$definesCustomDateTimeFormat && !$definesCustomDateIntervalFormat && !$definesCustomTimeFormat && !$definesCustomTemplates) {
            return $code;
        }

        $code = $code
            ->_use(Crud::class)
            ->_public()->_function()->_method('configureCrud', [], 'Crud')
            ->openBrace()
                ->_return()->_staticCall('Crud', 'new');

        if ($definesCustomDateFormat) {
            $code = $code->_methodCall('setDateFormat', [$this->phpDateFormatToIcuDateFormat($customDateFormat)]);
        }

        if ($definesCustomDateTimeFormat) {
            $code = $code->_methodCall('setDateTimeFormat', [$this->phpDateFormatToIcuDateFormat($customDateTimeFormat)]);
        }

        if ($definesCustomDateIntervalFormat) {
            $code = $code->_methodCall('setDateIntervalFormat', [$customDateIntervalFormat]);
        }

        if ($definesCustomTimeFormat) {
            $code = $code->_methodCall('setTimeFormat', [$this->phpDateFormatToIcuDateFormat($customTimeFormat)]);
        }

        if ($definesCustomTemplates) {
            foreach ($ea2Config['design']['templates'] as $oldTemplateName => $templatePath) {
                if (!$this->isCustomTemplate($templatePath)) {
                    continue;
                }

                $newTemplateName = $this->getNewTemplateNameFromOldTemplateName($oldTemplateName);
                $code = $code->_methodCall('overrideTemplate', [$newTemplateName, $templatePath]);
            }
        }

        $code = $code
            ->semiColon()
            ->closeBrace();

        return $code;
    }

    private function addConfigureUserMenuMethodInDashboardController(CodeBuilder $code, array $ea2Config): CodeBuilder
    {
        $displayName = $ea2Config['user']['display_name'];
        $definesCustomDisplayName = true !== $displayName;

        $displayAvatar = $ea2Config['user']['display_avatar'];
        $definesCustomDisplayAvatar = true !== $displayAvatar;

        if (!$definesCustomDisplayName && !$definesCustomDisplayAvatar) {
            return $code;
        }

        $code = $code
            ->_use(UserMenu::class)
            ->_use(UserInterface::class)
            ->_public()->_function()->_method('configureUserMenu', ['UserInterface $user'], 'UserMenu')
            ->openBrace()
                ->_return()->_staticCall('UserMenu', 'new');

        if ($definesCustomDisplayName) {
            $code = $code->_methodCall('displayUserName', [$displayName]);
        }

        if ($definesCustomDisplayAvatar) {
            $code = $code->_methodCall('displayUserAvatar', [$displayAvatar]);
        }

        $code = $code
            ->semiColon()
            ->closeBrace();

        return $code;
    }

    private function addConfigureMenuItemsMethodInDashboardController(CodeBuilder $code, array $ea2Config, array $entitiesFqcn): CodeBuilder
    {
        $mainMenuItems = $ea2Config['design']['menu'];
        $definesMenuItems = !empty($mainMenuItems);

        if (!$definesMenuItems) {
            return $code;
        }

        $code = $code
            ->_use(MenuItem::class)
            ->_public()->_function()->_method('configureMenuItems', [], 'iterable')
            ->openBrace();

        // first, generate the variables that hold the submenu items
        $numOfSubmenu = 0;
        foreach ($mainMenuItems as $menuItem) {
            if (empty($menuItem['children'])) {
                continue;
            }

            $code = $code->_variableName(sprintf('submenu%d', ++$numOfSubmenu))->equals()->openBracket()->newLine();
            foreach ($menuItem['children'] as $subMenuItem) {
                $code = $this->generateMenuItem($code, $ea2Config, $entitiesFqcn, $subMenuItem, false)->comma()->newLine();
            }
            $code = $code->newLine()->closeBracket()->semiColon();
        }

        // second, generate menu items and (optionally) refer to the variables generated above
        $numOfSubmenu = 0;
        foreach ($mainMenuItems as $menuItem) {
            if (!empty($menuItem['children'])) {
                ++$numOfSubmenu;
            }

            $code = $this->generateMenuItem($code, $ea2Config, $entitiesFqcn, $menuItem, true, $numOfSubmenu)->semiColon();
        }

        $code = $code->closeBrace();

        return $code;
    }

    private function generateMenuItem(CodeBuilder $code, array $ea2Config, array $entitiesFqcn, array $menuItem, bool $yieldResult, int $numOfSubmenu = 0)
    {
        $type = $menuItem['type'];
        $label = $menuItem['label'];
        $icon = $this->getNewIconClass($menuItem['icon']);
        $cssClass = $menuItem['css_class'];
        $target = $menuItem['target'];
        $rel = $menuItem['rel'];
        $permission = $menuItem['permission'];

        if ('entity' === $type) {
            $entityNameInMenuItem = $menuItem['entity'];
            $entityFqcnForMenuEntity = $ea2Config['entities'][$entityNameInMenuItem]['class'];
            if (!\in_array($entityFqcnForMenuEntity, $entitiesFqcn, true)) {
                return $code;
            }

            $entityClassName = u($entityFqcnForMenuEntity)->afterLast('\\')->toString();
            $code = $code
                ->_use($entityFqcnForMenuEntity)
                ->{ $yieldResult ? '_yield' : 'noop'}()->_staticCall('MenuItem', 'linkToCrud', [$label, $icon, $entityClassName.'::class']);

            if (isset($menuItem['params']['sortField'])) {
                $sortField = $menuItem['params']['sortField'];
                $sortDirection = $menuItem['params']['sortDirection'] ?? 'DESC';
                $sortConfig = [$sortField => $sortDirection];
                $code = $code->_methodCall('setDefaultSort', [$sortConfig]);
            }
        } elseif ('route' === $type) {
            $routeName = $menuItem['route'];
            $routeParameters = $menuItem['params'];
            $methodArguments = [$label, $icon, $routeName];
            if (!empty($routeParameters)) {
                $methodArguments[] = $routeParameters;
            }

            $code = $code->{ $yieldResult ? '_yield' : 'noop'}()->_staticCall('MenuItem', 'linktoRoute', $methodArguments);
        } elseif ('divider' === $type) {
            $methodArguments = [];
            if ($label) {
                $methodArguments[] = $label;
            }
            if ($icon) {
                $methodArguments[] = $icon;
            }

            $code = $code->{ $yieldResult ? '_yield' : 'noop'}()->_staticCall('MenuItem', 'section', $methodArguments);
        } elseif (!empty($menuItem['children'])) {
            $methodArguments = [$label];
            if ($icon) {
                $methodArguments[] = $icon;
            }

            $code = $code->{ $yieldResult ? '_yield' : 'noop'}()->_staticCall('MenuItem', 'subMenu', $methodArguments);
            $code = $code->_methodCallWithRawArguments('setSubItems', [sprintf('$submenu%d', $numOfSubmenu)]);
        } elseif ('link' === $menuItem['type']) {
            $methodArguments = [$label, $icon, $menuItem['url']];

            $code = $code->{ $yieldResult ? '_yield' : 'noop'}()->_staticCall('MenuItem', 'linkToUrl', $methodArguments);
        } else {
            return $code;
        }

        if ($target) {
            $code = $code->_methodCall('setLinkTarget', [$target]);
        }

        if ($rel) {
            $code = $code->_methodCall('setLinkRel', [$rel]);
        }

        if ($permission) {
            $code = $code->_methodCall('setPermission', [$permission]);
        }

        if ($cssClass) {
            $code = $code->_methodCall('setCssClass', [$cssClass]);
        }

        return $code;
    }

    private function phpDateFormatToIcuDateFormat(string $phpDateFormat): string
    {
        // remove all custom text included in the date format (e.g. 'd-m-Y \a\t H:i:s' -> 'd-m-Y H:i:s')
        $phpDateFormat = preg_replace('/\\./', '', $phpDateFormat);

        $icuDateFormat = u($phpDateFormat)
            // PHP format -> ICU format
            // source: https://nielsdefeyter.nl/archive/2015/cldr-php-dateformat-converter
            ->replace('y', 'yy')
            ->replace('Y', 'yyyy')
            ->replace('M', 'MMM')
            ->replace('F', 'MMMM')
            ->replace('m', 'MM')
            ->replace('d', 'dd')
            ->replace('j', 'd')
            ->replace('l', 'EEEE')
            ->replace('D', 'EEE')
            ->replace('a', 'a')
            ->replace('H', 'HH')
            ->replace('G', 'H')
            ->replace('h', 'h')
            ->replace('g', 'K')
            ->replace('i', 'mm')
            ->replace('s', 'ss')
            ->replace('T', 'z')
            ->replace('e', 'zzzz')
        ;

        return $icuDateFormat;
    }

    /**
     * Removes all styles related to layout from the given CSS styles.
     * This is needed because EA3 doesn't support them yet.
     */
    private function processCssClass(?string $cssClass): string
    {
        if (null === $cssClass) {
            return '';
        }

        $cssParts = explode(' ', $cssClass);
        $nonLayoutCssStyles = [];
        foreach ($cssParts as $cssPart) {
            if (u($cssPart)->match('/col\-(xs|sm|md|lg)\-\d+/')) {
                continue;
            }

            $nonLayoutCssStyles[] = $cssPart;
        }

        return implode(' ', $nonLayoutCssStyles);
    }

    private function processAndFilterFieldNames(array $fieldsConfig, array $newFieldNames): array
    {
        $updatedFieldNames = [];
        foreach ($fieldsConfig as $fieldName => $fieldConfig) {
            $isFormDesignElement = u($fieldName)->startsWith('_easyadmin_form_design_element');
            $isFormPanel = 'easyadmin_group' === $fieldConfig['type'];
            if ($isFormDesignElement && !$isFormPanel) {
                continue;
            }

            $updatedFieldNames[] = $newFieldNames[$fieldName] ?? $fieldName;
        }

        return $updatedFieldNames;
    }

    private function dumpCode(CodeBuilder $code, string $outputFilePath): bool
    {
        try {
            $rawSourceCode = $this->parser->parse($code->getAsString());
            $formattedSourceCode = $this->codePrettyPrinter->prettyPrintFile($rawSourceCode);
            $formattedSourceCode = $this->tweakFormattedSourceCode($formattedSourceCode);

            // this is needed to ensure that our formatting tweaks don't generate PHP code with syntax errors
            $this->parser->parse($formattedSourceCode);

            $this->fs->dumpFile($outputFilePath, $formattedSourceCode."\n");

            return true;
        } catch (\Throwable $e) {
            echo 'Parse Error: ', $e->getMessage();

            return false;
        }
    }

    private function tweakFormattedSourceCode(string $sourceCode): string
    {
        // this adds a blank line between the 'use' imports and the 'class' declaration
        $sourceCode = preg_replace('/^class /m', "\nclass ", $sourceCode);

        // this adds a blank line before each method that follows another method or property
        $sourceCode = str_replace("}\n    public function", "}\n\n    public function", $sourceCode);
        $sourceCode = str_replace(";\n    public function", ";\n\n    public function", $sourceCode);

        // this replaces 'function foo() : Foo' by 'function foo(): Foo'
        $sourceCode = preg_replace('/^(.*) function (.*)\((.*)\) : (.*)$/m', '$1 function $2($3): $4', $sourceCode);

        // this replaces '(yield ...);' by 'yield ...;'
        $sourceCode = preg_replace('/\(yield (.*)\);$/m', 'yield $1;', $sourceCode);

        // this adds a blank line before each if() statement
        $sourceCode = str_replace('        if (', "\n        if (", $sourceCode);

        // this formats submenu arrays in multiple lines
        $sourceCode = preg_replace_callback('/(?<variable_name>\$submenu\d+) \= \[(?<items>.*)\]\;$/m', static function ($matches) {
            $formattedItems = str_replace('MenuItem::', "\n            MenuItem::", $matches['items']);
            $formattedItems = str_replace(", \n", ",\n", $formattedItems);

            return $matches['variable_name'].' = ['
                .$formattedItems
                .",\n        ];\n\n";
        }, $sourceCode);

        // this adds a blank line before each section menu item
        $sourceCode = str_replace('        yield MenuItem::section(', "\n        yield MenuItem::section(", $sourceCode);

        // this breaks a single line with a variable and chained methods into multiple lines with one method in each line
        // (return $foo->method1()->method2()->method3()->...)
        $sourceCode = preg_replace_callback('/        return (?<variable_name>\$.*[^\-\>])\-\>(?<chained_methods>.*);/U', static function ($matches) {
            return '        return '.$matches['variable_name']
                ."\n            ->"
                .str_replace('->', "\n            ->", $matches['chained_methods']).';';
        }, $sourceCode);

        // this breaks a single line with a static call and chained methods into multiple lines with one method in each line
        // (return Foo::new()->method1()->method2()->method3()->...)
        $sourceCode = preg_replace_callback('/        return (?<class_name>.*)\:\:(?<method_name>.*)\(\)\-\>(?<chained_methods>.*);/U', static function ($matches) {
            return '        return '.$matches['class_name'].'::'.$matches['method_name'].'()'
                ."\n            ->"
                .str_replace('->', "\n            ->", $matches['chained_methods']).';';
        }, $sourceCode);

        // previous statements may add too many \n, make sure code doesn't contain more than two \n
        $sourceCode = preg_replace("/\n{3,}/", "\n\n", $sourceCode);

        return $sourceCode;
    }

    /**
     * Most EasyAdmin 2 apps used FontAwesome 4, whereas EasyAdmin 3 apps use
     * FontAwesome 5, which changes the icon prefix and some icon names.
     */
    private function getNewIconClass(?string $iconCssClass): string
    {
        if (empty($iconCssClass)) {
            return '';
        }

        // copied from https://fontawesome.com/how-to-use/on-the-web/setup/upgrading-from-version-4#name-changes
        $iconClassMap = [
            'fa-500px' => 'fab fa-500px',
            'fa-address-book-o' => 'far fa-address-book',
            'fa-address-card-o' => 'far fa-address-card',
            'fa-adn' => 'fab fa-adn',
            'fa-amazon' => 'fab fa-amazon',
            'fa-android' => 'fab fa-android',
            'fa-angellist' => 'fab fa-angellist',
            'fa-apple' => 'fab fa-apple',
            'fa-area-chart' => 'fas fa-chart-area',
            'fa-arrow-circle-o-down' => 'far fa-arrow-alt-circle-down',
            'fa-arrow-circle-o-left' => 'far fa-arrow-alt-circle-left',
            'fa-arrow-circle-o-right' => 'far fa-arrow-alt-circle-right',
            'fa-arrow-circle-o-up' => 'far fa-arrow-alt-circle-up',
            'fa-arrows' => 'fas fa-arrows-alt',
            'fa-arrows-alt' => 'fas fa-expand-arrows-alt',
            'fa-arrows-h' => 'fas fa-arrows-alt-h',
            'fa-arrows-v' => 'fas fa-arrows-alt-v',
            'fa-asl-interpreting' => 'fas fa-american-sign-language-interpreting',
            'fa-automobile' => 'fas fa-car',
            'fa-bandcamp' => 'fab fa-bandcamp',
            'fa-bank' => 'fas fa-university',
            'fa-bar-chart' => 'far fa-chart-bar',
            'fa-bar-chart-o' => 'far fa-chart-bar',
            'fa-bathtub' => 'fas fa-bath',
            'fa-battery' => 'fas fa-battery-full',
            'fa-battery-0' => 'fas fa-battery-empty',
            'fa-battery-1' => 'fas fa-battery-quarter',
            'fa-battery-2' => 'fas fa-battery-half',
            'fa-battery-3' => 'fas fa-battery-three-quarters',
            'fa-battery-4' => 'fas fa-battery-full',
            'fa-behance' => 'fab fa-behance',
            'fa-behance-square' => 'fab fa-behance-square',
            'fa-bell-o' => 'far fa-bell',
            'fa-bell-slash-o' => 'far fa-bell-slash',
            'fa-bitbucket' => 'fab fa-bitbucket',
            'fa-bitbucket-square' => 'fab fa-bitbucket',
            'fa-bitcoin' => 'fab fa-btc',
            'fa-black-tie' => 'fab fa-black-tie',
            'fa-bluetooth' => 'fab fa-bluetooth',
            'fa-bluetooth-b' => 'fab fa-bluetooth-b',
            'fa-bookmark-o' => 'far fa-bookmark',
            'fa-btc' => 'fab fa-btc',
            'fa-building-o' => 'far fa-building',
            'fa-buysellads' => 'fab fa-buysellads',
            'fa-cab' => 'fas fa-taxi',
            'fa-calendar' => 'fas fa-calendar-alt',
            'fa-calendar-check-o' => 'far fa-calendar-check',
            'fa-calendar-minus-o' => 'far fa-calendar-minus',
            'fa-calendar-o' => 'far fa-calendar',
            'fa-calendar-plus-o' => 'far fa-calendar-plus',
            'fa-calendar-times-o' => 'far fa-calendar-times',
            'fa-caret-square-o-down' => 'far fa-caret-square-down',
            'fa-caret-square-o-left' => 'far fa-caret-square-left',
            'fa-caret-square-o-right' => 'far fa-caret-square-right',
            'fa-caret-square-o-up' => 'far fa-caret-square-up',
            'fa-cc' => 'far fa-closed-captioning',
            'fa-cc-amex' => 'fab fa-cc-amex',
            'fa-cc-diners-club' => 'fab fa-cc-diners-club',
            'fa-cc-discover' => 'fab fa-cc-discover',
            'fa-cc-jcb' => 'fab fa-cc-jcb',
            'fa-cc-mastercard' => 'fab fa-cc-mastercard',
            'fa-cc-paypal' => 'fab fa-cc-paypal',
            'fa-cc-stripe' => 'fab fa-cc-stripe',
            'fa-cc-visa' => 'fab fa-cc-visa',
            'fa-chain' => 'fas fa-link',
            'fa-chain-broken' => 'fas fa-unlink',
            'fa-check-circle-o' => 'far fa-check-circle',
            'fa-check-square-o' => 'far fa-check-square',
            'fa-chrome' => 'fab fa-chrome',
            'fa-circle-o' => 'far fa-circle',
            'fa-circle-o-notch' => 'fas fa-circle-notch',
            'fa-circle-thin' => 'far fa-circle',
            'fa-clipboard' => 'far fa-clipboard',
            'fa-clock-o' => 'far fa-clock',
            'fa-clone' => 'far fa-clone',
            'fa-close' => 'fas fa-times',
            'fa-cloud-download' => 'fas fa-cloud-download-alt',
            'fa-cloud-upload' => 'fas fa-cloud-upload-alt',
            'fa-cny' => 'fas fa-yen-sign',
            'fa-code-fork' => 'fas fa-code-branch',
            'fa-codepen' => 'fab fa-codepen',
            'fa-codiepie' => 'fab fa-codiepie',
            'fa-comment-o' => 'far fa-comment',
            'fa-commenting' => 'fas fa-comment-dots',
            'fa-commenting-o' => 'far fa-comment-dots',
            'fa-comments-o' => 'far fa-comments',
            'fa-compass' => 'far fa-compass',
            'fa-connectdevelop' => 'fab fa-connectdevelop',
            'fa-contao' => 'fab fa-contao',
            'fa-copyright' => 'far fa-copyright',
            'fa-creative-commons' => 'fab fa-creative-commons',
            'fa-credit-card' => 'far fa-credit-card',
            'fa-credit-card-alt' => 'fas fa-credit-card',
            'fa-css3' => 'fab fa-css3',
            'fa-cutlery' => 'fas fa-utensils',
            'fa-dashboard' => 'fas fa-tachometer-alt',
            'fa-dashcube' => 'fab fa-dashcube',
            'fa-deafness' => 'fas fa-deaf',
            'fa-dedent' => 'fas fa-outdent',
            'fa-delicious' => 'fab fa-delicious',
            'fa-deviantart' => 'fab fa-deviantart',
            'fa-diamond' => 'far fa-gem',
            'fa-digg' => 'fab fa-digg',
            'fa-dollar' => 'fas fa-dollar-sign',
            'fa-dot-circle-o' => 'far fa-dot-circle',
            'fa-dribbble' => 'fab fa-dribbble',
            'fa-drivers-license' => 'fas fa-id-card',
            'fa-drivers-license-o' => 'far fa-id-card',
            'fa-dropbox' => 'fab fa-dropbox',
            'fa-drupal' => 'fab fa-drupal',
            'fa-edge' => 'fab fa-edge',
            'fa-eercast' => 'fab fa-sellcast',
            'fa-empire' => 'fab fa-empire',
            'fa-envelope-o' => 'far fa-envelope',
            'fa-envelope-open-o' => 'far fa-envelope-open',
            'fa-envira' => 'fab fa-envira',
            'fa-etsy' => 'fab fa-etsy',
            'fa-eur' => 'fas fa-euro-sign',
            'fa-euro' => 'fas fa-euro-sign',
            'fa-exchange' => 'fas fa-exchange-alt',
            'fa-expeditedssl' => 'fab fa-expeditedssl',
            'fa-external-link' => 'fas fa-external-link-alt',
            'fa-external-link-square' => 'fas fa-external-link-square-alt',
            'fa-eye' => 'far fa-eye',
            'fa-eye-slash' => 'far fa-eye-slash',
            'fa-eyedropper' => 'fas fa-eye-dropper',
            'fa-fa' => 'fab fa-font-awesome',
            'fa-facebook' => 'fab fa-facebook-f',
            'fa-facebook-f' => 'fab fa-facebook-f',
            'fa-facebook-official' => 'fab fa-facebook',
            'fa-facebook-square' => 'fab fa-facebook-square',
            'fa-feed' => 'fas fa-rss',
            'fa-file-archive-o' => 'far fa-file-archive',
            'fa-file-audio-o' => 'far fa-file-audio',
            'fa-file-code-o' => 'far fa-file-code',
            'fa-file-excel-o' => 'far fa-file-excel',
            'fa-file-image-o' => 'far fa-file-image',
            'fa-file-movie-o' => 'far fa-file-video',
            'fa-file-o' => 'far fa-file',
            'fa-file-pdf-o' => 'far fa-file-pdf',
            'fa-file-photo-o' => 'far fa-file-image',
            'fa-file-picture-o' => 'far fa-file-image',
            'fa-file-powerpoint-o' => 'far fa-file-powerpoint',
            'fa-file-sound-o' => 'far fa-file-audio',
            'fa-file-text' => 'fas fa-file-alt',
            'fa-file-text-o' => 'far fa-file-alt',
            'fa-file-video-o' => 'far fa-file-video',
            'fa-file-word-o' => 'far fa-file-word',
            'fa-file-zip-o' => 'far fa-file-archive',
            'fa-files-o' => 'far fa-copy',
            'fa-firefox' => 'fab fa-firefox',
            'fa-first-order' => 'fab fa-first-order',
            'fa-flag-o' => 'far fa-flag',
            'fa-flash' => 'fas fa-bolt',
            'fa-flickr' => 'fab fa-flickr',
            'fa-floppy-o' => 'far fa-save',
            'fa-folder-o' => 'far fa-folder',
            'fa-folder-open-o' => 'far fa-folder-open',
            'fa-font-awesome' => 'fab fa-font-awesome',
            'fa-fonticons' => 'fab fa-fonticons',
            'fa-fort-awesome' => 'fab fa-fort-awesome',
            'fa-forumbee' => 'fab fa-forumbee',
            'fa-foursquare' => 'fab fa-foursquare',
            'fa-free-code-camp' => 'fab fa-free-code-camp',
            'fa-frown-o' => 'far fa-frown',
            'fa-futbol-o' => 'far fa-futbol',
            'fa-gbp' => 'fas fa-pound-sign',
            'fa-ge' => 'fab fa-empire',
            'fa-gear' => 'fas fa-cog',
            'fa-gears' => 'fas fa-cogs',
            'fa-get-pocket' => 'fab fa-get-pocket',
            'fa-gg' => 'fab fa-gg',
            'fa-gg-circle' => 'fab fa-gg-circle',
            'fa-git' => 'fab fa-git',
            'fa-git-square' => 'fab fa-git-square',
            'fa-github' => 'fab fa-github',
            'fa-github-alt' => 'fab fa-github-alt',
            'fa-github-square' => 'fab fa-github-square',
            'fa-gitlab' => 'fab fa-gitlab',
            'fa-gittip' => 'fab fa-gratipay',
            'fa-glass' => 'fas fa-glass-martini',
            'fa-glide' => 'fab fa-glide',
            'fa-glide-g' => 'fab fa-glide-g',
            'fa-google' => 'fab fa-google',
            'fa-google-plus' => 'fab fa-google-plus-g',
            'fa-google-plus-circle' => 'fab fa-google-plus',
            'fa-google-plus-official' => 'fab fa-google-plus',
            'fa-google-plus-square' => 'fab fa-google-plus-square',
            'fa-google-wallet' => 'fab fa-google-wallet',
            'fa-gratipay' => 'fab fa-gratipay',
            'fa-grav' => 'fab fa-grav',
            'fa-group' => 'fas fa-users',
            'fa-hacker-news' => 'fab fa-hacker-news',
            'fa-hand-grab-o' => 'far fa-hand-rock',
            'fa-hand-lizard-o' => 'far fa-hand-lizard',
            'fa-hand-o-down' => 'far fa-hand-point-down',
            'fa-hand-o-left' => 'far fa-hand-point-left',
            'fa-hand-o-right' => 'far fa-hand-point-right',
            'fa-hand-o-up' => 'far fa-hand-point-up',
            'fa-hand-paper-o' => 'far fa-hand-paper',
            'fa-hand-peace-o' => 'far fa-hand-peace',
            'fa-hand-pointer-o' => 'far fa-hand-pointer',
            'fa-hand-rock-o' => 'far fa-hand-rock',
            'fa-hand-scissors-o' => 'far fa-hand-scissors',
            'fa-hand-spock-o' => 'far fa-hand-spock',
            'fa-hand-stop-o' => 'far fa-hand-paper',
            'fa-handshake-o' => 'far fa-handshake',
            'fa-hard-of-hearing' => 'fas fa-deaf',
            'fa-hdd-o' => 'far fa-hdd',
            'fa-header' => 'fas fa-heading',
            'fa-heart-o' => 'far fa-heart',
            'fa-hospital-o' => 'far fa-hospital',
            'fa-hotel' => 'fas fa-bed',
            'fa-hourglass-1' => 'fas fa-hourglass-start',
            'fa-hourglass-2' => 'fas fa-hourglass-half',
            'fa-hourglass-3' => 'fas fa-hourglass-end',
            'fa-hourglass-o' => 'far fa-hourglass',
            'fa-houzz' => 'fab fa-houzz',
            'fa-html5' => 'fab fa-html5',
            'fa-id-badge' => 'far fa-id-badge',
            'fa-id-card-o' => 'far fa-id-card',
            'fa-ils' => 'fas fa-shekel-sign',
            'fa-image' => 'far fa-image',
            'fa-imdb' => 'fab fa-imdb',
            'fa-inr' => 'fas fa-rupee-sign',
            'fa-instagram' => 'fab fa-instagram',
            'fa-institution' => 'fas fa-university',
            'fa-internet-explorer' => 'fab fa-internet-explorer',
            'fa-intersex' => 'fas fa-transgender',
            'fa-ioxhost' => 'fab fa-ioxhost',
            'fa-joomla' => 'fab fa-joomla',
            'fa-jpy' => 'fas fa-yen-sign',
            'fa-jsfiddle' => 'fab fa-jsfiddle',
            'fa-keyboard-o' => 'far fa-keyboard',
            'fa-krw' => 'fas fa-won-sign',
            'fa-lastfm' => 'fab fa-lastfm',
            'fa-lastfm-square' => 'fab fa-lastfm-square',
            'fa-leanpub' => 'fab fa-leanpub',
            'fa-legal' => 'fas fa-gavel',
            'fa-lemon-o' => 'far fa-lemon',
            'fa-level-down' => 'fas fa-level-down-alt',
            'fa-level-up' => 'fas fa-level-up-alt',
            'fa-life-bouy' => 'far fa-life-ring',
            'fa-life-buoy' => 'far fa-life-ring',
            'fa-life-ring' => 'far fa-life-ring',
            'fa-life-saver' => 'far fa-life-ring',
            'fa-lightbulb-o' => 'far fa-lightbulb',
            'fa-line-chart' => 'fas fa-chart-line',
            'fa-linkedin' => 'fab fa-linkedin-in',
            'fa-linkedin-square' => 'fab fa-linkedin',
            'fa-linode' => 'fab fa-linode',
            'fa-linux' => 'fab fa-linux',
            'fa-list-alt' => 'far fa-list-alt',
            'fa-long-arrow-down' => 'fas fa-long-arrow-alt-down',
            'fa-long-arrow-left' => 'fas fa-long-arrow-alt-left',
            'fa-long-arrow-right' => 'fas fa-long-arrow-alt-right',
            'fa-long-arrow-up' => 'fas fa-long-arrow-alt-up',
            'fa-mail-forward' => 'fas fa-share',
            'fa-mail-reply' => 'fas fa-reply',
            'fa-mail-reply-all' => 'fas fa-reply-all',
            'fa-map-marker' => 'fas fa-map-marker-alt',
            'fa-map-o' => 'far fa-map',
            'fa-maxcdn' => 'fab fa-maxcdn',
            'fa-meanpath' => 'fab fa-font-awesome',
            'fa-medium' => 'fab fa-medium',
            'fa-meetup' => 'fab fa-meetup',
            'fa-meh-o' => 'far fa-meh',
            'fa-minus-square-o' => 'far fa-minus-square',
            'fa-mixcloud' => 'fab fa-mixcloud',
            'fa-mobile' => 'fas fa-mobile-alt',
            'fa-mobile-phone' => 'fas fa-mobile-alt',
            'fa-modx' => 'fab fa-modx',
            'fa-money' => 'far fa-money-bill-alt',
            'fa-moon-o' => 'far fa-moon',
            'fa-mortar-board' => 'fas fa-graduation-cap',
            'fa-navicon' => 'fas fa-bars',
            'fa-newspaper-o' => 'far fa-newspaper',
            'fa-object-group' => 'far fa-object-group',
            'fa-object-ungroup' => 'far fa-object-ungroup',
            'fa-odnoklassniki' => 'fab fa-odnoklassniki',
            'fa-odnoklassniki-square' => 'fab fa-odnoklassniki-square',
            'fa-opencart' => 'fab fa-opencart',
            'fa-openid' => 'fab fa-openid',
            'fa-opera' => 'fab fa-opera',
            'fa-optin-monster' => 'fab fa-optin-monster',
            'fa-pagelines' => 'fab fa-pagelines',
            'fa-paper-plane-o' => 'far fa-paper-plane',
            'fa-paste' => 'far fa-clipboard',
            'fa-pause-circle-o' => 'far fa-pause-circle',
            'fa-paypal' => 'fab fa-paypal',
            'fa-pencil' => 'fas fa-pencil-alt',
            'fa-pencil-square' => 'fas fa-pen-square',
            'fa-pencil-square-o' => 'far fa-edit',
            'fa-photo' => 'far fa-image',
            'fa-picture-o' => 'far fa-image',
            'fa-pie-chart' => 'fas fa-chart-pie',
            'fa-pied-piper' => 'fab fa-pied-piper',
            'fa-pied-piper-alt' => 'fab fa-pied-piper-alt',
            'fa-pied-piper-pp' => 'fab fa-pied-piper-pp',
            'fa-pinterest' => 'fab fa-pinterest',
            'fa-pinterest-p' => 'fab fa-pinterest-p',
            'fa-pinterest-square' => 'fab fa-pinterest-square',
            'fa-play-circle-o' => 'far fa-play-circle',
            'fa-plus-square-o' => 'far fa-plus-square',
            'fa-product-hunt' => 'fab fa-product-hunt',
            'fa-qq' => 'fab fa-qq',
            'fa-question-circle-o' => 'far fa-question-circle',
            'fa-quora' => 'fab fa-quora',
            'fa-ra' => 'fab fa-rebel',
            'fa-ravelry' => 'fab fa-ravelry',
            'fa-rebel' => 'fab fa-rebel',
            'fa-reddit' => 'fab fa-reddit',
            'fa-reddit-alien' => 'fab fa-reddit-alien',
            'fa-reddit-square' => 'fab fa-reddit-square',
            'fa-refresh' => 'fas fa-sync',
            'fa-registered' => 'far fa-registered',
            'fa-remove' => 'fas fa-times',
            'fa-renren' => 'fab fa-renren',
            'fa-reorder' => 'fas fa-bars',
            'fa-repeat' => 'fas fa-redo',
            'fa-resistance' => 'fab fa-rebel',
            'fa-rmb' => 'fas fa-yen-sign',
            'fa-rotate-left' => 'fas fa-undo',
            'fa-rotate-right' => 'fas fa-redo',
            'fa-rouble' => 'fas fa-ruble-sign',
            'fa-rub' => 'fas fa-ruble-sign',
            'fa-ruble' => 'fas fa-ruble-sign',
            'fa-rupee' => 'fas fa-rupee-sign',
            'fa-s15' => 'fas fa-bath',
            'fa-safari' => 'fab fa-safari',
            'fa-scissors' => 'fas fa-cut',
            'fa-scribd' => 'fab fa-scribd',
            'fa-sellsy' => 'fab fa-sellsy',
            'fa-send' => 'fas fa-paper-plane',
            'fa-send-o' => 'far fa-paper-plane',
            'fa-share-square-o' => 'far fa-share-square',
            'fa-shekel' => 'fas fa-shekel-sign',
            'fa-sheqel' => 'fas fa-shekel-sign',
            'fa-shield' => 'fas fa-shield-alt',
            'fa-shirtsinbulk' => 'fab fa-shirtsinbulk',
            'fa-sign-in' => 'fas fa-sign-in-alt',
            'fa-sign-out' => 'fas fa-sign-out-alt',
            'fa-signing' => 'fas fa-sign-language',
            'fa-simplybuilt' => 'fab fa-simplybuilt',
            'fa-skyatlas' => 'fab fa-skyatlas',
            'fa-skype' => 'fab fa-skype',
            'fa-slack' => 'fab fa-slack',
            'fa-sliders' => 'fas fa-sliders-h',
            'fa-slideshare' => 'fab fa-slideshare',
            'fa-smile-o' => 'far fa-smile',
            'fa-snapchat' => 'fab fa-snapchat',
            'fa-snapchat-ghost' => 'fab fa-snapchat-ghost',
            'fa-snapchat-square' => 'fab fa-snapchat-square',
            'fa-snowflake-o' => 'far fa-snowflake',
            'fa-soccer-ball-o' => 'far fa-futbol',
            'fa-sort-alpha-asc' => 'fas fa-sort-alpha-down',
            'fa-sort-alpha-desc' => 'fas fa-sort-alpha-up',
            'fa-sort-amount-asc' => 'fas fa-sort-amount-down',
            'fa-sort-amount-desc' => 'fas fa-sort-amount-up',
            'fa-sort-asc' => 'fas fa-sort-up',
            'fa-sort-desc' => 'fas fa-sort-down',
            'fa-sort-numeric-asc' => 'fas fa-sort-numeric-down',
            'fa-sort-numeric-desc' => 'fas fa-sort-numeric-up',
            'fa-soundcloud' => 'fab fa-soundcloud',
            'fa-spoon' => 'fas fa-utensil-spoon',
            'fa-spotify' => 'fab fa-spotify',
            'fa-square-o' => 'far fa-square',
            'fa-stack-exchange' => 'fab fa-stack-exchange',
            'fa-stack-overflow' => 'fab fa-stack-overflow',
            'fa-star-half-empty' => 'far fa-star-half',
            'fa-star-half-full' => 'far fa-star-half',
            'fa-star-half-o' => 'far fa-star-half',
            'fa-star-o' => 'far fa-star',
            'fa-steam' => 'fab fa-steam',
            'fa-steam-square' => 'fab fa-steam-square',
            'fa-sticky-note-o' => 'far fa-sticky-note',
            'fa-stop-circle-o' => 'far fa-stop-circle',
            'fa-stumbleupon' => 'fab fa-stumbleupon',
            'fa-stumbleupon-circle' => 'fab fa-stumbleupon-circle',
            'fa-sun-o' => 'far fa-sun',
            'fa-superpowers' => 'fab fa-superpowers',
            'fa-support' => 'far fa-life-ring',
            'fa-tablet' => 'fas fa-tablet-alt',
            'fa-tachometer' => 'fas fa-tachometer-alt',
            'fa-telegram' => 'fab fa-telegram',
            'fa-television' => 'fas fa-tv',
            'fa-tencent-weibo' => 'fab fa-tencent-weibo',
            'fa-themeisle' => 'fab fa-themeisle',
            'fa-thermometer' => 'fas fa-thermometer-full',
            'fa-thermometer-0' => 'fas fa-thermometer-empty',
            'fa-thermometer-1' => 'fas fa-thermometer-quarter',
            'fa-thermometer-2' => 'fas fa-thermometer-half',
            'fa-thermometer-3' => 'fas fa-thermometer-three-quarters',
            'fa-thermometer-4' => 'fas fa-thermometer-full',
            'fa-thumb-tack' => 'fas fa-thumbtack',
            'fa-thumbs-o-down' => 'far fa-thumbs-down',
            'fa-thumbs-o-up' => 'far fa-thumbs-up',
            'fa-ticket' => 'fas fa-ticket-alt',
            'fa-times-circle-o' => 'far fa-times-circle',
            'fa-times-rectangle' => 'fas fa-window-close',
            'fa-times-rectangle-o' => 'far fa-window-close',
            'fa-toggle-down' => 'far fa-caret-square-down',
            'fa-toggle-left' => 'far fa-caret-square-left',
            'fa-toggle-right' => 'far fa-caret-square-right',
            'fa-toggle-up' => 'far fa-caret-square-up',
            'fa-trash' => 'fas fa-trash-alt',
            'fa-trash-o' => 'far fa-trash-alt',
            'fa-trello' => 'fab fa-trello',
            'fa-tripadvisor' => 'fab fa-tripadvisor',
            'fa-try' => 'fas fa-lira-sign',
            'fa-tumblr' => 'fab fa-tumblr',
            'fa-tumblr-square' => 'fab fa-tumblr-square',
            'fa-turkish-lira' => 'fas fa-lira-sign',
            'fa-twitch' => 'fab fa-twitch',
            'fa-twitter' => 'fab fa-twitter',
            'fa-twitter-square' => 'fab fa-twitter-square',
            'fa-unsorted' => 'fas fa-sort',
            'fa-usb' => 'fab fa-usb',
            'fa-usd' => 'fas fa-dollar-sign',
            'fa-user-circle-o' => 'far fa-user-circle',
            'fa-user-o' => 'far fa-user',
            'fa-vcard' => 'fas fa-address-card',
            'fa-vcard-o' => 'far fa-address-card',
            'fa-viacoin' => 'fab fa-viacoin',
            'fa-viadeo' => 'fab fa-viadeo',
            'fa-viadeo-square' => 'fab fa-viadeo-square',
            'fa-video-camera' => 'fas fa-video',
            'fa-vimeo' => 'fab fa-vimeo-v',
            'fa-vimeo-square' => 'fab fa-vimeo-square',
            'fa-vine' => 'fab fa-vine',
            'fa-vk' => 'fab fa-vk',
            'fa-volume-control-phone' => 'fas fa-phone-volume',
            'fa-warning' => 'fas fa-exclamation-triangle',
            'fa-wechat' => 'fab fa-weixin',
            'fa-weibo' => 'fab fa-weibo',
            'fa-weixin' => 'fab fa-weixin',
            'fa-whatsapp' => 'fab fa-whatsapp',
            'fa-wheelchair-alt' => 'fab fa-accessible-icon',
            'fa-wikipedia-w' => 'fab fa-wikipedia-w',
            'fa-window-close-o' => 'far fa-window-close',
            'fa-window-maximize' => 'far fa-window-maximize',
            'fa-window-restore' => 'far fa-window-restore',
            'fa-windows' => 'fab fa-windows',
            'fa-won' => 'fas fa-won-sign',
            'fa-wordpress' => 'fab fa-wordpress',
            'fa-wpbeginner' => 'fab fa-wpbeginner',
            'fa-wpexplorer' => 'fab fa-wpexplorer',
            'fa-wpforms' => 'fab fa-wpforms',
            'fa-xing' => 'fab fa-xing',
            'fa-xing-square' => 'fab fa-xing-square',
            'fa-y-combinator' => 'fab fa-y-combinator',
            'fa-y-combinator-square' => 'fab fa-hacker-news',
            'fa-yahoo' => 'fab fa-yahoo',
            'fa-yc' => 'fab fa-y-combinator',
            'fa-yc-square' => 'fab fa-hacker-news',
            'fa-yelp' => 'fab fa-yelp',
            'fa-yen' => 'fas fa-yen-sign',
            'fa-yoast' => 'fab fa-yoast',
            'fa-youtube' => 'fab fa-youtube',
            'fa-youtube-play' => 'fab fa-youtube',
            'fa-youtube-square' => 'fab fa-youtube-square',
        ];

        $specialCssClasses = [
            'fa-fw', 'fa-lg', 'fa-2x', 'fa-3x', 'fa-4x', 'fa-5x', 'fa-ul', 'fa-li',
            'fa-border', 'fa-pull-right', 'fa-pull-left', 'fa-pulse', 'fa-spin',
            'fa-rotate-90', 'fa-rotate-180', 'fa-rotate-270', 'fa-flip-horizontal',
            'fa-flip-vertical', 'fa-stack', 'fa-stack-1x', 'fa-stack-2x', 'fa-inverse',
        ];

        $iconCssClassParts = explode(' ', u($iconCssClass)->collapseWhitespace());
        $newIconCssClassParts = [];
        foreach ($iconCssClassParts as $cssClass) {
            // this is the old FontAwesome 4 prefix which is no longer recommended
            if ('fa' === $cssClass) {
                continue;
            }

            if (u($cssClass)->startsWith('fa-') && !\in_array($cssClass, $specialCssClasses, true)) {
                if (\array_key_exists($cssClass, $iconClassMap)) {
                    $cssClass = $iconClassMap[$cssClass];
                } else {
                    $newIconCssClassParts[] = 'fas';
                }
            }

            $newIconCssClassParts[] = $cssClass;
        }

        return u(' ')->join(array_unique($newIconCssClassParts))->toString();
    }
}
