<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Maker;

use Doctrine\DBAL\Types\Type;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\UserMenu;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
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
use EasyCorp\Bundle\EasyAdminBundle\Field\TextAreaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
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

            $code = CodeBuilder::new()
                ->_namespace($this->namespace)
                ->_use(AbstractCrudController::class)
                ->_use($entityFqcn)
                ->_class(sprintf('%sCrudController', $entityClassName))->_extends('AbstractCrudController')
                ->openBrace()
                    ->_public()->_static()->_variableName('entityFqcn')->_variableValue($entityClassName.'::class')->semiColon();

            $code = $this->addConfigureCrudMethod($code, $entityClassName, $entityConfig);
            $code = $this->addConfigureActionsMethod($code, $entityClassName, $entityConfig);
            $code = $this->addConfigureFieldsMethod($code, $entityClassName, $entityConfig);

            $code = $code->closeBrace(); // closing for 'class ... {'

            $controllerClassName = $entityClassName.'CrudController.php';
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

        if (!$definesCustomLabel && !$definesCustomTemplates && !$definesCustomHelp && !$definesCustomPagination && !$definesCustomEntityPermission && !$definesCustomActionDropdown) {
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

        if ($definesCustomHelp) {
            foreach (['list' => 'index', 'edit' => 'edit', 'show' => 'detail', 'new' => 'new'] as $oldPageName => $newPageName) {
                if (null !== $helpMessage = $entityConfig[$oldPageName]['help']) {
                    $code = $code->_methodCall('setHelp', [$newPageName, $helpMessage]);
                }
            }
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

    private function addConfigureActionsMethod(CodeBuilder $code, string $entityClassName, array $entityConfig): CodeBuilder
    {
        if (empty($entityConfig['disabled_actions'])) {
            return $code;
        }

        $code = $code
            ->_use(Actions::class)
            ->_public()->_function()->_method('configureActions', ['Actions $actions'], 'Actions')
            ->openBrace()
            ->_return()->_variableName('actions')
            ->_methodCall('disableActions', [$entityConfig['disabled_actions']])
            ->semiColon()
            ->closeBrace();

        return $code;
    }

    private function addConfigureFieldsMethod(CodeBuilder $code, string $entityClassName, array $entityConfig): CodeBuilder
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

            $code = $code->_use($fieldFqcn);
            $code = $code->_variableName($fieldName)->equals()->_staticCall($fieldClassName, 'new', $methodArguments);

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
                //'field_array' => 'crud/field/array',
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
                //'field_json_array' => 'crud/field/json_array',
                'field_integer' => 'crud/field/integer',
                //'field_object' => 'crud/field/object',
                'field_percent' => 'crud/field/percent',
                //'field_raw' => 'crud/field/raw',
                //'field_simple_array' => 'crud/field/simple_array',
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
            //Type::TARRAY => 'array',
            Type::BIGINT => TextField::class,
            Type::BINARY => TextAreaField::class,
            Type::BLOB => TextAreaField::class,
            Type::BOOLEAN => BooleanField::class,
            Type::DATE => DateField::class,
            Type::DATE_IMMUTABLE => DateField::class,
            Type::DATEINTERVAL => TextField::class,
            Type::DATETIME => DateTimeField::class,
            Type::DATETIME_IMMUTABLE => DateTimeField::class,
            Type::DATETIMETZ => DateTimeField::class,
            Type::DATETIMETZ_IMMUTABLE => DateTimeField::class,
            Type::DECIMAL => NumberField::class,
            Type::FLOAT => NumberField::class,
            Type::GUID => TextField::class,
            Type::INTEGER => IntegerField::class,
            Type::JSON => TextField::class,
            Type::OBJECT => TextField::class,
            //Type::SIMPLE_ARRAY => 'array',
            Type::SMALLINT => IntegerField::class,
            Type::STRING => TextField::class,
            Type::TEXT => TextAreaField::class,
            Type::TIME => TimeField::class,
            Type::TIME_IMMUTABLE => TimeField::class,
        ];

        return $fieldTypeToFqcn[$fieldType] ?? $doctrineTypeToFqcn[$doctrineDataType] ?? Field::class;
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
        $icon = u($menuItem['icon'])->ensureStart('fa ')->toString();
        $cssClass = $menuItem['css_class'];
        $target = $menuItem['target'];
        $rel = $menuItem['rel'];
        $permission = $menuItem['permission'];

        if ('entity' === $type) {
            $entityNameInMenuItem = $menuItem['entity'];
            $entityFqcnForMenuEntity = $ea2Config['entities'][$entityNameInMenuItem]['class'];
            if (!\in_array($entityFqcnForMenuEntity, $entitiesFqcn)) {
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
        //dump($code->getAsString());
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
        $sourceCode = preg_replace_callback('/(?<variable_name>\$submenu\d+) \= \[(?<items>.*)\]\;$/m', function ($matches) {
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
        $sourceCode = preg_replace_callback('/        return (?<variable_name>\$.*[^\-\>])\-\>(?<chained_methods>.*);/U', function ($matches) {
            return '        return '.$matches['variable_name']
                ."\n            ->"
                .str_replace('->', "\n            ->", $matches['chained_methods']).';';
        }, $sourceCode);

        // this breaks a single line with a static call and chained methods into multiple lines with one method in each line
        // (return Foo::new()->method1()->method2()->method3()->...)
        $sourceCode = preg_replace_callback('/        return (?<class_name>.*)\:\:(?<method_name>.*)\(\)\-\>(?<chained_methods>.*);/U', function ($matches) {
            return '        return '.$matches['class_name'].'::'.$matches['method_name'].'()'
                ."\n            ->"
                .str_replace('->', "\n            ->", $matches['chained_methods']).';';
        }, $sourceCode);

        // previous statements may add too many \n, make sure code doesn't contain more than two \n
        $sourceCode = preg_replace("/\n{3,}/", "\n\n", $sourceCode);

        return $sourceCode;
    }
}
