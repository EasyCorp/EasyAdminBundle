<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Configuration;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class CrudConfigBuilder
{
    private $entityFqcn;
    private $labelInSingular;
    private $labelInPlural;
    private $dateFormat = 'Y-m-d';
    private $timeFormat = 'H:i:s';
    private $dateTimeFormat = 'F j, Y H:i';
    private $dateIntervalFormat = '%%y Year(s) %%m Month(s) %%d Day(s)';
    private $numberFormat;

    public function entityClass(string $entityFqcn): self
    {
        $this->entityFqcn = $entityFqcn;

        return $this;
    }

    public function labelInSingular(string $label): self
    {
        $this->labelInSingular = $label;

        return $this;
    }

    public function labelInPlural(string $label): self
    {
        $this->labelInPlural = $label;

        return $this;
    }

    public function dateFormat(string $format): self
    {
        $this->dateFormat = $format;

        return $this;
    }

    public function timeFormat(string $format): self
    {
        $this->timeFormat = $format;

        return $this;
    }

    public function dateTimeFormat(string $format): self
    {
        $this->dateTimeFormat = $format;

        return $this;
    }

    public function dateIntervalFormat(string $format): self
    {
        $this->dateIntervalFormat = $format;

        return $this;
    }

    public function numberFormat(string $format): self
    {
        $this->numberFormat = $format;

        return $this;
    }
}
