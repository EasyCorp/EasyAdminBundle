<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use Symfony\Component\Form\Extension\Core\Type\TimeType;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class TimeField implements FieldInterface
{
    use FieldTrait;

    public const OPTION_TIME_PATTERN = 'timePattern';

    public static function new(string $propertyName, ?string $label = null): self
    {
        return (new self())
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setTemplateName('crud/field/time')
            ->setFormType(TimeType::class)
            ->addCssClass('field-time')
            // the proper default values of these options are set on the Crud class
            ->setCustomOption(self::OPTION_TIME_PATTERN, null)
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
     * @param string $timeFormatOrPattern A format name ('short', 'medium', 'long', 'full') or a valid ICU Datetime Pattern (see http://userguide.icu-project.org/formatparse/datetime)
     */
    public function setFormat(string $timeFormatOrPattern): self
    {
        if (DateTimeField::FORMAT_NONE === $timeFormatOrPattern || '' === trim($timeFormatOrPattern)) {
            $validTimeFormatsWithoutNone = array_filter(DateTimeField::VALID_DATE_FORMATS, static function($format) {
                return DateTimeField::FORMAT_NONE !== $format;
            });

            throw new \InvalidArgumentException(sprintf('The first argument of the "%s()" method cannot be "%s" or an empty string. Use either the special time formats (%s) or a datetime Intl pattern.',  __METHOD__, DateTimeField::FORMAT_NONE, implode(', ', $validTimeFormatsWithoutNone)));
        }

        $timePattern = DateTimeField::INTL_TIME_PATTERNS[$timeFormatOrPattern] ?? $timeFormatOrPattern;
        $this->setCustomOption(self::OPTION_TIME_PATTERN, $timePattern);

        return $this;
    }
}
