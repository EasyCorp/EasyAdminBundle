<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Property;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfigInterface;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;

class TimeProperty implements PropertyConfigInterface
{
    use PropertyConfigTrait;

    public function __construct()
    {
        $this
            ->setType('time')
            ->setFormType(DateTimeType::class)
            ->setTemplateName('property/time')
            // the proper default values of these options are set on the CrudConfig class
            ->setCustomOption(DateTimeProperty::OPTION_TIME_FORMAT, null)
            ->setCustomOption(DateTimeProperty::OPTION_DATETIME_PATTERN, null)
            ->setCustomOption(DateTimeProperty::OPTION_TIMEZONE, null);
    }

    /**
     * @param string $timezoneId A valid PHP timezone ID
     */
    public function setTimezone(string $timezoneId): self
    {
        if (!\in_array($timezoneId, timezone_identifiers_list())) {
            throw new \InvalidArgumentException(sprintf('The "%s" timezone is not a valid PHP timezone ID. Use any of the values listed at https://www.php.net/manual/en/timezones.php', $timezoneId));
        }

        $this->setCustomOption(DateTimeProperty::OPTION_TIMEZONE, $timezoneId);

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

        if (!\in_array($timeFormatOrPattern, DateTimeProperty::VALID_DATE_FORMATS, true)) {
            $this->setCustomOption(DateTimeProperty::OPTION_DATETIME_PATTERN, $timeFormatOrPattern);
            $this->setCustomOption(DateTimeProperty::OPTION_TIME_FORMAT, null);
        } else {
            $this->setCustomOption(DateTimeProperty::OPTION_DATETIME_PATTERN, null);
            $this->setCustomOption(DateTimeProperty::OPTION_TIME_FORMAT, $timeFormatOrPattern);
        }

        return $this;
    }
}
