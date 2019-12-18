<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Configuration;

use EasyCorp\Bundle\EasyAdminBundle\Collection\TemplateDtoCollection;
use EasyCorp\Bundle\EasyAdminBundle\Dto\TemplateDto;

final class TemplateRegistry
{
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
        'label/empty' => '@EasyAdmin/label/empty.html.twig',
        'label/inaccessible' => '@EasyAdmin/label/inaccessible.html.twig',
        'label/null' => '@EasyAdmin/label/null.html.twig',
        'label/undefined' => '@EasyAdmin/label/undefined.html.twig',
        'property/array' => '@EasyAdmin/crud/property/array.html.twig',
        'property/association' => '@EasyAdmin/field_association.html.twig',
        'property/avatar' => '@EasyAdmin/crud/property/avatar.html.twig',
        'property/bigint' => '@EasyAdmin/crud/property/bigint.html.twig',
        'property/boolean' => '@EasyAdmin/crud/property/boolean.html.twig',
        'property/country' => '@EasyAdmin/crud/property/country.html.twig',
        'property/date' => '@EasyAdmin/crud/property/date.html.twig',
        'property/datetime' => '@EasyAdmin/crud/property/datetime.html.twig',
        'property/datetimetz' => '@EasyAdmin/crud/property/datetimetz.html.twig',
        'property/decimal' => '@EasyAdmin/crud/property/decimal.html.twig',
        'property/email' => '@EasyAdmin/crud/property/email.html.twig',
        'property/float' => '@EasyAdmin/crud/property/float.html.twig',
        'property/id' => '@EasyAdmin/crud/property/id.html.twig',
        'property/image' => '@EasyAdmin/crud/property/image.html.twig',
        'property/integer' => '@EasyAdmin/crud/property/integer.html.twig',
        'property/raw' => '@EasyAdmin/crud/property/raw.html.twig',
        'property/simple_array' => '@EasyAdmin/crud/property/simple_array.html.twig',
        'property/smallint' => '@EasyAdmin/crud/property/smallint.html.twig',
        'property/string' => '@EasyAdmin/crud/property/string.html.twig',
        'property/tel' => '@EasyAdmin/crud/property/tel.html.twig',
        'property/text' => '@EasyAdmin/crud/property/text.html.twig',
        'property/time' => '@EasyAdmin/crud/property/time.html.twig',
        'property/toggle' => '@EasyAdmin/crud/property/toggle.html.twig',
        'property/url' => '@EasyAdmin/crud/property/url.html.twig',
    ];
    private static $templates;

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
        if (!array_key_exists($templateName, self::$templates)) {
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
