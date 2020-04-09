<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class DateTimeField implements FieldInterface
{
    use FieldTrait;

    public const VALID_DATE_FORMATS = ['none', 'short', 'medium', 'long', 'full'];

    public const OPTION_DATE_FORMAT = 'dateFormat';
    public const OPTION_DATETIME_PATTERN = 'dateTimePattern';
    public const OPTION_TIME_FORMAT = 'timeFormat';
    public const OPTION_TIMEZONE = 'timezone';

    public static function new(string $propertyName, ?string $label = null): self
    {
        return (new self())
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setTemplateName('crud/field/datetime')
            ->setFormType(DateTimeType::class)
            ->addCssClass('field-datetime')
            // the proper default values of these options are set on the Crud class
            ->setCustomOption(self::OPTION_DATE_FORMAT, null)
            ->setCustomOption(self::OPTION_TIME_FORMAT, null)
            ->setCustomOption(self::OPTION_DATETIME_PATTERN, null)
            ->setCustomOption(self::OPTION_TIMEZONE, null);
    }

    /**
     * @param string $timezoneId A valid PHP timezone ID
     */
    public function setTimezone(string $timezoneId): self
    {
        if (!\in_array($timezoneId, timezone_identifiers_list(), true)) {
            throw new \InvalidArgumentException(sprintf('The "%s" timezone is not a valid PHP timezone ID. Use any of the values listed at https://www.php.net/manual/en/timezones.php', $timezoneId));
        }

        $this->setCustomOption(self::OPTION_TIMEZONE, $timezoneId);

        return $this;
    }

    /**
     * @param string $dateFormatOrPattern A format name ('none', 'short', 'medium', 'long', 'full') or a valid ICU Datetime Pattern (see http://userguide.icu-project.org/formatparse/datetime)
     * @param string $timeFormat          A format name ('none', 'short', 'medium', 'long', 'full')
     */
    public function setFormat(string $dateFormatOrPattern, string $timeFormat = 'none'): self
    {
        if ('' === trim($dateFormatOrPattern)) {
            throw new \InvalidArgumentException(sprintf('The first argument of the "%s()" method cannot be an empty string. Define either the date format or the datetime Intl pattern.', __METHOD__));
        }

        if ('none' === $dateFormatOrPattern && 'none' === $timeFormat) {
            throw new \InvalidArgumentException(sprintf('The values of the arguments of "%s()" cannot be "none" at the same time. Change any of them (or both).', __METHOD__));
        }

        $isDatePattern = !\in_array($dateFormatOrPattern, self::VALID_DATE_FORMATS, true);

        if ($isDatePattern && 'none' !== $timeFormat) {
            throw new \InvalidArgumentException(sprintf('When the first argument of "%s()" is a datetime pattern, you cannot set the time format in the second argument (define the time format as part of the datetime pattern).', __METHOD__));
        }

        if (!$isDatePattern && !\in_array($timeFormat, self::VALID_DATE_FORMATS, true)) {
            throw new \InvalidArgumentException(sprintf('The value of the time format can only be one of the following: %s (but "%s" was given).', implode(', ', self::VALID_DATE_FORMATS), $timeFormat));
        }

        if (!\in_array($dateFormatOrPattern, self::VALID_DATE_FORMATS, true)) {
            $this->setCustomOption(self::OPTION_DATETIME_PATTERN, $dateFormatOrPattern);
            $this->setCustomOption(self::OPTION_DATE_FORMAT, null);
            $this->setCustomOption(self::OPTION_TIME_FORMAT, null);
        } else {
            $this->setCustomOption(self::OPTION_DATETIME_PATTERN, null);
            $this->setCustomOption(self::OPTION_DATE_FORMAT, $dateFormatOrPattern);
            $this->setCustomOption(self::OPTION_TIME_FORMAT, $timeFormat);
        }

        return $this;
    }
}
