<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;

class TimeField implements FieldInterface
{
    use FieldTrait;

    public function __construct()
    {
        $this
            ->setType('time')
            ->setFormType(DateTimeType::class)
            ->setTemplateName('crud/field/time')
            // the proper default values of these options are set on the Crud class
            ->setCustomOption(DateTimeField::OPTION_TIME_FORMAT, null)
            ->setCustomOption(DateTimeField::OPTION_DATETIME_PATTERN, null)
            ->setCustomOption(DateTimeField::OPTION_TIMEZONE, null);
    }

    /**
     * @param string $timezoneId A valid PHP timezone ID
     */
    public function setTimezone(string $timezoneId): self
    {
        if (!\in_array($timezoneId, timezone_identifiers_list())) {
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
        if ('' === trim($timeFormatOrPattern) || 'none' === $timeFormatOrPattern) {
            throw new \InvalidArgumentException(sprintf('The first argument of the "%s()" method cannot be "none" or an empty string. Define either the time format or the datetime Intl pattern.', __METHOD__));
        }

        if (!\in_array($timeFormatOrPattern, DateTimeField::VALID_DATE_FORMATS, true)) {
            $this->setCustomOption(DateTimeField::OPTION_DATETIME_PATTERN, $timeFormatOrPattern);
            $this->setCustomOption(DateTimeField::OPTION_TIME_FORMAT, null);
        } else {
            $this->setCustomOption(DateTimeField::OPTION_DATETIME_PATTERN, null);
            $this->setCustomOption(DateTimeField::OPTION_TIME_FORMAT, $timeFormatOrPattern);
        }

        return $this;
    }
}
