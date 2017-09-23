<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Util;

/**
 * Utility class to map Symfony 2.x short form types to Symfony 3.x FQCN form types.
 *
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 *
 * @internal
 */
final class LegacyFormHelper
{
    private static $supportedTypes = array(
        // Symfony's built-in types
        'birthday' => 'Symfony\\Component\\Form\\Extension\\Core\\Type\\BirthdayType',
        'button' => 'Symfony\\Component\\Form\\Extension\\Core\\Type\\ButtonType',
        'checkbox' => 'Symfony\\Component\\Form\\Extension\\Core\\Type\\CheckboxType',
        'choice' => 'Symfony\\Component\\Form\\Extension\\Core\\Type\\ChoiceType',
        'collection' => 'Symfony\\Component\\Form\\Extension\\Core\\Type\\CollectionType',
        'country' => 'Symfony\\Component\\Form\\Extension\\Core\\Type\\CountryType',
        'currency' => 'Symfony\\Component\\Form\\Extension\\Core\\Type\\CurrencyType',
        'datetime' => 'Symfony\\Component\\Form\\Extension\\Core\\Type\\DateTimeType',
        'date' => 'Symfony\\Component\\Form\\Extension\\Core\\Type\\DateType',
        'email' => 'Symfony\\Component\\Form\\Extension\\Core\\Type\\EmailType',
        'entity' => 'Symfony\\Bridge\\Doctrine\\Form\\Type\\EntityType',
        'file' => 'Symfony\\Component\\Form\\Extension\\Core\\Type\\FileType',
        'form' => 'Symfony\\Component\\Form\\Extension\\Core\\Type\\FormType',
        'hidden' => 'Symfony\\Component\\Form\\Extension\\Core\\Type\\HiddenType',
        'integer' => 'Symfony\\Component\\Form\\Extension\\Core\\Type\\IntegerType',
        'language' => 'Symfony\\Component\\Form\\Extension\\Core\\Type\\LanguageType',
        'locale' => 'Symfony\\Component\\Form\\Extension\\Core\\Type\\LocaleType',
        'money' => 'Symfony\\Component\\Form\\Extension\\Core\\Type\\MoneyType',
        'number' => 'Symfony\\Component\\Form\\Extension\\Core\\Type\\NumberType',
        'password' => 'Symfony\\Component\\Form\\Extension\\Core\\Type\\PasswordType',
        'percent' => 'Symfony\\Component\\Form\\Extension\\Core\\Type\\PercentType',
        'radio' => 'Symfony\\Component\\Form\\Extension\\Core\\Type\\RadioType',
        'range' => 'Symfony\\Component\\Form\\Extension\\Core\\Type\\RangeType',
        'repeated' => 'Symfony\\Component\\Form\\Extension\\Core\\Type\\RepeatedType',
        'reset' => 'Symfony\\Component\\Form\\Extension\\Core\\Type\\ResetType',
        'search' => 'Symfony\\Component\\Form\\Extension\\Core\\Type\\SearchType',
        'submit' => 'Symfony\\Component\\Form\\Extension\\Core\\Type\\SubmitType',
        'textarea' => 'Symfony\\Component\\Form\\Extension\\Core\\Type\\TextareaType',
        'text' => 'Symfony\\Component\\Form\\Extension\\Core\\Type\\TextType',
        'time' => 'Symfony\\Component\\Form\\Extension\\Core\\Type\\TimeType',
        'timezone' => 'Symfony\\Component\\Form\\Extension\\Core\\Type\\TimezoneType',
        'url' => 'Symfony\\Component\\Form\\Extension\\Core\\Type\\UrlType',
        // EasyAdmin custom types
        'easyadmin' => 'EasyCorp\\Bundle\\EasyAdminBundle\\Form\\Type\\EasyAdminFormType',
        'easyadmin_autocomplete' => 'EasyCorp\\Bundle\\EasyAdminBundle\\Form\\Type\\EasyAdminAutocompleteType',
        'easyadmin_divider' => 'EasyCorp\\Bundle\\EasyAdminBundle\\Form\\Type\\EasyAdminDividerType',
        'easyadmin_group' => 'EasyCorp\\Bundle\\EasyAdminBundle\\Form\\Type\\EasyAdminGroupType',
        'easyadmin_section' => 'EasyCorp\\Bundle\\EasyAdminBundle\\Form\\Type\\EasyAdminSectionType',
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
        return false === class_exists('Symfony\\Component\\Form\\Util\\StringUtil');
    }
}

class_alias('EasyCorp\Bundle\EasyAdminBundle\Form\Util\LegacyFormHelper', 'JavierEguiluz\Bundle\EasyAdminBundle\Form\Util\LegacyFormHelper', false);
