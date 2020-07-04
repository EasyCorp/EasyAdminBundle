<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use Symfony\Component\Form\Extension\Core\Type\DateType;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class DateField implements FieldInterface
{
    use FieldTrait;

    public const OPTION_DATE_PATTERN = 'datePattern';

    public static function new(string $propertyName, ?string $label = null): self
    {
        return (new self())
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setTemplateName('crud/field/date')
            ->setFormType(DateType::class)
            ->addCssClass('field-date')
            // the proper default values of these options are set on the Crud class
            ->setCustomOption(self::OPTION_DATE_PATTERN, null)
            ->setCustomOption(DateTimeField::OPTION_TIMEZONE, null);
    }

    /**
     * @param string $timezoneId A valid PHP timezone ID
     */
    public function setTimezone(string $timezoneId): self
    {
        if (!\in_array($timezoneId, timezone_identifiers_list(), true)) {
            throw new \InvalidArgumentException(sprintf('The "%s" timezone is not a valid PHP timezone ID. Use any of the values listed at https://www.php.net/manual/en/timezones.php', $timezoneId));
        }

        $this->setCustomOption(DateTimeField::OPTION_TIMEZONE, $timezoneId);

        return $this;
    }

    /**
     * @param string $dateFormatOrPattern A format name ('short', 'medium', 'long', 'full') or a valid ICU Datetime Pattern (see http://userguide.icu-project.org/formatparse/datetime)
     */
    public function setFormat(string $dateFormatOrPattern): self
    {
        if (DateTimeField::FORMAT_NONE === $dateFormatOrPattern || '' === trim($dateFormatOrPattern)) {
            $validDateFormatsWithoutNone = array_filter(DateTimeField::VALID_DATE_FORMATS, static function($format) {
                return DateTimeField::FORMAT_NONE !== $format;
            });

            throw new \InvalidArgumentException(sprintf('The first argument of the "%s()" method cannot be "%s" or an empty string. Use either the special date formats (%s) or a datetime Intl pattern.',  __METHOD__, DateTimeField::FORMAT_NONE, implode(', ', $validDateFormatsWithoutNone)));
        }

        $datePattern = DateTimeField::INTL_DATE_PATTERNS[$dateFormatOrPattern] ?? $dateFormatOrPattern;
        $this->setCustomOption(self::OPTION_DATE_PATTERN, $datePattern);

        return $this;
    }
}
