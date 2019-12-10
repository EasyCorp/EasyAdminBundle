<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Configuration;

use EasyCorp\Bundle\EasyAdminBundle\Collection\TemplateCollection;

final class TemplateRegistry
{
    private static $templateNamesAndPaths = [
        'layout' => '@EasyAdmin/layout.html.twig',
        'main_menu' => '@EasyAdmin/default/menu.html.twig',
        'paginator' => '@EasyAdmin/paginator.html.twig',
        'index' => '@EasyAdmin/index.html.twig',
        'detail' => '@EasyAdmin/detail.html.twig',
        'action' => '@EasyAdmin/action.html.twig',
        'filters' => '@EasyAdmin/filters.html.twig',
        'exception' => '@EasyAdmin/exception.html.twig',
        'flash_messages' => '@EasyAdmin/default/flash_messages.html.twig',
        'label/empty' => '@EasyAdmin/label_empty.html.twig',
        'label/inaccessible' => '@EasyAdmin/label_inaccessible.html.twig',
        'label/null' => '@EasyAdmin/label_null.html.twig',
        'label/undefined' => '@EasyAdmin/label_undefined.html.twig',
        'property/array' => '@EasyAdmin/field_array.html.twig',
        'property/association' => '@EasyAdmin/field_association.html.twig',
        'property/avatar' => '@EasyAdmin/field_avatar.html.twig',
        'property/bigint' => '@EasyAdmin/field_bigint.html.twig',
        'property/boolean' => '@EasyAdmin/field_boolean.html.twig',
        'property/country' => '@EasyAdmin/field_country.html.twig',
        'property/date' => '@EasyAdmin/field_date.html.twig',
        'property/datetime' => '@EasyAdmin/field_datetime.html.twig',
        'property/datetimetz' => '@EasyAdmin/field_datetimetz.html.twig',
        'property/decimal' => '@EasyAdmin/field_decimal.html.twig',
        'property/email' => '@EasyAdmin/field_email.html.twig',
        'property/float' => '@EasyAdmin/field_float.html.twig',
        'property/id' => '@EasyAdmin/field_id.html.twig',
        'property/image' => '@EasyAdmin/field_image.html.twig',
        'property/integer' => '@EasyAdmin/field_integer.html.twig',
        'property/raw' => '@EasyAdmin/field_raw.html.twig',
        'property/simple_array' => '@EasyAdmin/field_simple_array.html.twig',
        'property/smallint' => '@EasyAdmin/field_smallint.html.twig',
        'property/string' => '@EasyAdmin/field_string.html.twig',
        'property/tel' => '@EasyAdmin/field_tel.html.twig',
        'property/text' => '@EasyAdmin/field_text.html.twig',
        'property/time' => '@EasyAdmin/field_time.html.twig',
        'property/toggle' => '@EasyAdmin/field_toggle.html.twig',
        'property/url' => '@EasyAdmin/field_url.html.twig',
    ];

    private function __construct()
    {
    }

    public static function new(): self
    {
        return new self();
    }

    public static function getTemplateNames(): array
    {
        return array_keys(self::$templateNamesAndPaths);
    }

    public function addTemplateCollection(TemplateCollection $templateCollection): self
    {
        self::$templateNamesAndPaths = array_merge(self::$templateNamesAndPaths, iterator_to_array($templateCollection));

        return $this;
    }

    public function getPath(string $templateName): string
    {
        if (!array_key_exists($templateName, self::$templateNamesAndPaths)) {
            throw new \InvalidArgumentException(sprintf('The "%s" template is not defined in EasyAdmin. Use one of these allowed template names: %s', $templateName, implode(', ', array_keys(self::$templateNamesAndPaths))));
        }

        return self::$templateNamesAndPaths[$templateName];
    }
}
