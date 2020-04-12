<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Maker;

use Doctrine\DBAL\Types\Type;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
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
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use function Symfony\Component\String\u;

final class Migrator
{
    public function migrate(array $ea2Config, string $outputDir, string $namespace, SymfonyStyle $io): void
    {
        $fs = new Filesystem();

        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $codePrettyPrinter = new Standard();
        foreach ($ea2Config['entities'] as $entityName => $entityConfig) {
            $entityFqcn = $entityConfig['class'];
            $entityClassName = u($entityFqcn)->afterLast('\\')->toString();

            $code = CodeBuilder::new()
                ->_namespace($namespace)
                ->_use(AbstractCrudController::class)
                ->_use($entityFqcn)
                ->_class(sprintf('%sCrudController', $entityClassName))->_extends('AbstractCrudController')
                ->openBrace()
                ->_public()->_static()->_variableName('entityFqcn')->_variableValue($entityClassName.'::class')->semiColon()
                ->newLine()
            ;

            $code = $this->addConfigureCrudMethod($code, $entityClassName, $entityConfig);
            $code = $this->addConfigureActionsMethod($code, $entityClassName, $entityConfig);
            $code = $this->addConfigureFieldsMethod($code, $entityClassName, $entityConfig);

            $code = $code->closeBrace(); // closing for 'class ... {'
            //dump($code->getAsString());
            try {
                $rawSourceCode = $parser->parse($code->getAsString());
                $formattedSourceCode = $codePrettyPrinter->prettyPrintFile($rawSourceCode);
                $formattedSourceCode = $this->tweakFormattedSourceCode($formattedSourceCode);
                // this is needed to ensure that our formatting tweaks don't generate PHP code with syntax errors
                $parser->parse($formattedSourceCode);

                $controllerClassName = $entityClassName.'CrudController.php';
                $fs->dumpFile($outputDir.'/'.$controllerClassName, $formattedSourceCode);
                $io->text(sprintf(' // Generated %s', $controllerClassName));
            } catch (\Throwable $e) {
                // there some error in the generated PHP code
                echo 'Parse Error: ', $e->getMessage();
            }
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

        foreach ($entityConfig['list']['fields'] as $fieldName => $fieldConfig) {
            $fieldFqcn = $this->guessFieldFqcnForProperty($fieldConfig);
            $fieldClassName = u($fieldFqcn)->afterLast('\\')->toString();

            $methodArguments = [$fieldName];
            $fieldLabel = $fieldConfig['label'];
            $humanizedLabel = $this->humanizeString($fieldLabel);
            // in EA2, an empty label means no label (same as FALSE in EA3
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

            if (!empty($fieldConfig['css_class'])) {
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
            Type::DATETIMETZ => 'datetimetz',
            Type::DATETIMETZ_IMMUTABLE => 'datetimetz',
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

    private function isCustomTemplate(string $templatePath): bool
    {
        return !u($templatePath)->startsWith('@EasyAdmin/');
    }

    private function humanizeString(string $string): string
    {
        return ucfirst(mb_strtolower(trim(preg_replace(['/([A-Z])/', '/[_\s]+/'], ['_$1', ' '], $string))));
    }

    private function tweakFormattedSourceCode(string $sourceCode): string
    {
        // this adds a blank line between the 'use' imports and the 'class' declaration
        $sourceCode = preg_replace('/^class /m', "\nclass ", $sourceCode);

        // this adds a blank line before each method
        $sourceCode = str_replace('    public function', "\n    public function", $sourceCode);

        // this replaces 'function foo() : Foo' by 'function foo(): Foo'
        $sourceCode = preg_replace('/^(.*) function (.*)\((.*)\) : (.*)$/m', '$1 function $2($3): $4', $sourceCode);

        // this breaks a single line with chained methods into multiple lines with one method in each line
        $sourceCode = preg_replace_callback('/        return (?<variable_name>\$.*[^\-\>])\-\>(?<chained_methods>.*);/U', function ($matches) {
            return '        return '.$matches['variable_name']
                ."\n            ->"
                .str_replace('->', "\n            ->", $matches['chained_methods']).';';
        }, $sourceCode);

        return $sourceCode;
    }
}
