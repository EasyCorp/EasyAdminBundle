<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Util;

use EasyCorp\Bundle\EasyAdminBundle\Form\Type\EasyAdminAutocompleteType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\EasyAdminDividerType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\EasyAdminFormType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\EasyAdminGroupType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\EasyAdminSectionType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\CurrencyType;
use Symfony\Component\Form\Extension\Core\Type\DateIntervalType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\LanguageType;
use Symfony\Component\Form\Extension\Core\Type\LocaleType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\PercentType;
use Symfony\Component\Form\Extension\Core\Type\RadioType;
use Symfony\Component\Form\Extension\Core\Type\RangeType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\ResetType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;

/*
 * Utility class to map shortcut form types (e.g. `text` or `submit`) to its
 * associated FQCN type.
 *
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 *
 * @internal
 */
final class FormTypeHelper
{
    private static $nameToClassMap = [
        // Symfony's built-in types
        'birthday' => BirthdayType::class,
        'button' => ButtonType::class,
        'checkbox' => CheckboxType::class,
        'choice' => ChoiceType::class,
        'collection' => CollectionType::class,
        'color' => ColorType::class,
        'country' => CountryType::class,
        'currency' => CurrencyType::class,
        'datetime' => DateTimeType::class,
        'datetime_immutable' => DateTimeType::class,
        'date' => DateType::class,
        'date_immutable' => DateType::class,
        'date_interval' => DateIntervalType::class,
        'email' => EmailType::class,
        'entity' => EntityType::class,
        'file' => FileType::class,
        'form' => FormType::class,
        'hidden' => HiddenType::class,
        'integer' => IntegerType::class,
        'language' => LanguageType::class,
        'locale' => LocaleType::class,
        'money' => MoneyType::class,
        'number' => NumberType::class,
        'password' => PasswordType::class,
        'percent' => PercentType::class,
        'radio' => RadioType::class,
        'range' => RangeType::class,
        'repeated' => RepeatedType::class,
        'reset' => ResetType::class,
        'search' => SearchType::class,
        'submit' => SubmitType::class,
        'tel' => TelType::class,
        'textarea' => TextareaType::class,
        'text' => TextType::class,
        'time' => TimeType::class,
        'time_immutable' => TimeType::class,
        'timezone' => TimezoneType::class,
        'url' => UrlType::class,
        // EasyAdmin custom types
        'easyadmin' => EasyAdminFormType::class,
        'easyadmin_autocomplete' => EasyAdminAutocompleteType::class,
        'easyadmin_divider' => EasyAdminDividerType::class,
        'easyadmin_group' => EasyAdminGroupType::class,
        'easyadmin_section' => EasyAdminSectionType::class,
        // Popular third-party bundles types
        'ckeditor' => 'Ivory\\CKEditorBundle\\Form\\Type\\CKEditorType',
        'fos_ckeditor' => 'FOS\\CKEditorBundle\\Form\\Type\\CKEditorType',
        'vich_file' => 'Vich\\UploaderBundle\\Form\\Type\\VichFileType',
        'vich_image' => 'Vich\\UploaderBundle\\Form\\Type\\VichImageType',
    ];

    /**
     * It returns the FQCN of the given short type name. If the FQCN is not
     * found, it returs the given value.
     *
     * @param string $typeName
     *
     * @return string
     */
    public static function getTypeClass($typeName)
    {
        return self::$nameToClassMap[$typeName] ?? $typeName;
    }

    /**
     * It returns the short type name of the given FQCN. If the type name is not
     * found, it returns the given value.
     *
     * @param string $typeFqcn
     *
     * @return string
     */
    public static function getTypeName($typeFqcn)
    {
        // needed to avoid collisions between immutable and non-immutable date types,
        // which are mapped to the same Symfony Form type classes
        $filteredNameToClassMap = \array_filter(self::$nameToClassMap, function ($typeName) {
            return !\in_array($typeName, ['datetime_immutable', 'date_immutable', 'time_immutable']);
        }, ARRAY_FILTER_USE_KEY);
        $classToNameMap = \array_flip($filteredNameToClassMap);

        return $classToNameMap[$typeFqcn] ?? $typeFqcn;
    }
}
