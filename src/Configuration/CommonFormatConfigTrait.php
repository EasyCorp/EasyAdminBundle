<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Configuration;

trait CommonFormatConfigTrait
{
    private $dateFormat = 'Y-m-d';
    private $timeFormat = 'H:i:s';
    private $dateTimeFormat = 'F j, Y H:i';
    private $dateIntervalFormat = '%%y Year(s) %%m Month(s) %%d Day(s)';
    private $numberFormat;

    public function setDateFormat(string $format): self
    {
        $this->dateFormat = $format;

        return $this;
    }

    public function setTimeFormat(string $format): self
    {
        $this->timeFormat = $format;

        return $this;
    }

    public function setDateTimeFormat(string $format): self
    {
        $this->dateTimeFormat = $format;

        return $this;
    }

    public function setDateIntervalFormat(string $format): self
    {
        $this->dateIntervalFormat = $format;

        return $this;
    }

    public function setNumberFormat(string $format): self
    {
        $this->numberFormat = $format;

        return $this;
    }
}
