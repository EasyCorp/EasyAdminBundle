<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Util;

/*
 * Utility class to map Symfony 2.x short form types to Symfony 3.x FQCN form types.
 *
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 *
 * @internal
 */
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
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\CurrencyType;
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
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\Util\StringUtil;

final class LegacyFormHelper
{
    private static $supportedTypes = array(
        // Symfony's built-in types
        'birthday' => BirthdayType::class,
        'button' => ButtonType::class,
        'checkbox' => CheckboxType::class,
        'choice' => ChoiceType::class,
        'collection' => CollectionType::class,
        'country' => CountryType::class,
        'currency' => CurrencyType::class,
        'datetime' => DateTimeType::class,
        'datetime_immutable' => DateTimeType::class,
        'date' => DateType::class,
        'date_immutable' => DateType::class,
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
        'vich_file' => 'Vich\\UploaderBundle\\Form\\Type\\VichFileType',
        'vich_image' => 'Vich\\UploaderBundle\\Form\\Type\\VichImageType',
    );

    /**
     * It returns the FQCN of the given short type name if not use legacy form
     * and its a supported type, otherwise return the same type name
     *
     * @param string $shortType
     *
     * @return string
     */
    public static function getType($shortType)
    {
        if (self::useLegacyFormComponent() || !isset(self::$supportedTypes[$shortType])) {
            return $shortType;
        }

        return self::$supportedTypes[$shortType];
    }

    /**
     * It returns the short type name of the given FQCN
     *
     * @param string $fqcn
     *
     * @return string
     */
    public static function getShortType($fqcn)
    {
        $flippedTypes = array_flip(self::$supportedTypes);

        if (!isset($flippedTypes[$fqcn])) {
            return $fqcn;
        }

        return $flippedTypes[$fqcn];
    }

    /**
     * Returns true if the legacy Form component is being used by the application.
     *
     * @return bool
     */
    public static function useLegacyFormComponent()
    {
        return false === class_exists(StringUtil::class);
    }
}
