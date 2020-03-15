<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Registry;

use EasyCorp\Bundle\EasyAdminBundle\Collection\TemplateDtoCollection;
use EasyCorp\Bundle\EasyAdminBundle\Dto\TemplateDto;

final class TemplateRegistry
{
    private static $templates;
    private static $templateNamesAndPaths = [
        'layout' => '@EasyAdmin/layout.html.twig',
        'main_menu' => '@EasyAdmin/menu.html.twig',
        'exception' => '@EasyAdmin/exception.html.twig',
        'flash_messages' => '@EasyAdmin/flash_messages.html.twig',
        'crud/paginator' => '@EasyAdmin/crud/paginator.html.twig',
        'crud/index' => '@EasyAdmin/crud/index.html.twig',
        'crud/detail' => '@EasyAdmin/crud/detail.html.twig',
        'crud/new' => '@EasyAdmin/crud/new.html.twig',
        'crud/edit' => '@EasyAdmin/crud/edit.html.twig',
        'crud/action' => '@EasyAdmin/crud/action.html.twig',
        'crud/filters' => '@EasyAdmin/crud/filters.html.twig',
        'crud/field/array' => '@EasyAdmin/crud/field/array.html.twig',
        'crud/field/association' => '@EasyAdmin/crud/field/association.html.twig',
        'crud/field/avatar' => '@EasyAdmin/crud/field/avatar.html.twig',
        'crud/field/bigint' => '@EasyAdmin/crud/field/bigint.html.twig',
        'crud/field/boolean' => '@EasyAdmin/crud/field/boolean.html.twig',
        'crud/field/code_editor' => '@EasyAdmin/crud/field/code_editor.html.twig',
        'crud/field/collection' => '@EasyAdmin/crud/field/collection.html.twig',
        'crud/field/color' => '@EasyAdmin/crud/field/color.html.twig',
        'crud/field/country' => '@EasyAdmin/crud/field/country.html.twig',
        'crud/field/currency' => '@EasyAdmin/crud/field/currency.html.twig',
        'crud/field/date' => '@EasyAdmin/crud/field/date.html.twig',
        'crud/field/datetime' => '@EasyAdmin/crud/field/datetime.html.twig',
        'crud/field/datetimetz' => '@EasyAdmin/crud/field/datetimetz.html.twig',
        'crud/field/decimal' => '@EasyAdmin/crud/field/decimal.html.twig',
        'crud/field/email' => '@EasyAdmin/crud/field/email.html.twig',
        'crud/field/float' => '@EasyAdmin/crud/field/float.html.twig',
        'crud/field/generic' => '@EasyAdmin/crud/field/generic.html.twig',
        'crud/field/id' => '@EasyAdmin/crud/field/id.html.twig',
        'crud/field/image' => '@EasyAdmin/crud/field/image.html.twig',
        'crud/field/integer' => '@EasyAdmin/crud/field/integer.html.twig',
        'crud/field/language' => '@EasyAdmin/crud/field/language.html.twig',
        'crud/field/money' => '@EasyAdmin/crud/field/money.html.twig',
        'crud/field/number' => '@EasyAdmin/crud/field/number.html.twig',
        'crud/field/percent' => '@EasyAdmin/crud/field/percent.html.twig',
        'crud/field/select' => '@EasyAdmin/crud/field/select.html.twig',
        'crud/field/raw' => '@EasyAdmin/crud/field/raw.html.twig',
        'crud/field/simple_array' => '@EasyAdmin/crud/field/simple_array.html.twig',
        'crud/field/smallint' => '@EasyAdmin/crud/field/smallint.html.twig',
        'crud/field/telephone' => '@EasyAdmin/crud/field/telephone.html.twig',
        'crud/field/text' => '@EasyAdmin/crud/field/text.html.twig',
        'crud/field/textarea' => '@EasyAdmin/crud/field/textarea.html.twig',
        'crud/field/text_editor' => '@EasyAdmin/crud/field/text_editor.html.twig',
        'crud/field/time' => '@EasyAdmin/crud/field/time.html.twig',
        'crud/field/timezone' => '@EasyAdmin/crud/field/timezone.html.twig',
        'crud/field/toggle' => '@EasyAdmin/crud/field/toggle.html.twig',
        'crud/field/url' => '@EasyAdmin/crud/field/url.html.twig',
        'label/empty' => '@EasyAdmin/label/empty.html.twig',
        'label/inaccessible' => '@EasyAdmin/label/inaccessible.html.twig',
        'label/null' => '@EasyAdmin/label/null.html.twig',
        'label/undefined' => '@EasyAdmin/label/undefined.html.twig',
    ];

    private function __construct()
    {
    }

    public static function new(): self
    {
        $registry = new self();
        $registry::initialize();

        return $registry;
    }

    public static function getTemplateNames(): array
    {
        self::initialize();

        return array_keys(self::$templates);
    }

    public function addTemplates(TemplateDtoCollection $templates): self
    {
        self::$templates = array_merge(self::$templates, iterator_to_array($templates));

        return $this;
    }

    public function get(string $templateName): TemplateDto
    {
        if (!\array_key_exists($templateName, self::$templates)) {
            throw new \InvalidArgumentException(sprintf('The "%s" template is not defined in EasyAdmin. Use one of these allowed template names: %s', $templateName, implode(', ', array_keys(self::$templates))));
        }

        return self::$templates[$templateName];
    }

    private static function initialize(): void
    {
        if (null !== self::$templates) {
            return;
        }

        $templatesDto = [];
        foreach (self::$templateNamesAndPaths as $name => $path) {
            $templatesDto[$name] = new TemplateDto($name, $path);
        }

        self::$templates = $templatesDto;
    }
}
