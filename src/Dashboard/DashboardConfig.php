<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dashboard;

/**
 * Holds the configuration options of the dashboard.
 *
 * The methods of this class are both getters and setters at the same time. This is
 * generally a bad practice. However, doing this allows to have a fluent and expressive
 * configuration and also a concise and expressive template code. Twig tries "property()"
 * before "getProperty()", so if you define "property()" for the fluent config, Twig
 * will try to use it to get the config value too and it will fail.
 */
final class DashboardConfig
{
    private $siteName = 'EasyAdmin';
    private $dateFormat = 'Y-m-d';
    private $timeFormat = 'H:i:s';
    private $dateTimeFormat = 'F j, Y H:i';
    private $dateIntervalFormat = '%%y Year(s) %%m Month(s) %%d Day(s)';
    private $numberFormat = '';
    private $translationDomain = 'messages';
    private $disabledActions = [];

    public static function new(): self
    {
        return new self();
    }

    /**
     * @return $this|string|null
     */
    public function siteName(string $name = null)
    {
        if (0 === func_num_args()) {
            return $this->siteName;
        }

        $this->siteName = $name;

        return $this;
    }

    /**
     * @return $this|string|null
     */
    public function dateFormat(string $dateFormat = null)
    {
        if (0 === func_num_args()) {
            return $this->dateFormat;
        }

        $this->dateFormat = $dateFormat;

        return $this;
    }

    /**
     * @return $this|string|null
     */
    public function timeFormat(string $timeFormat = null)
    {
        if (0 === func_num_args()) {
            return $this->timeFormat;
        }

        $this->timeFormat = $timeFormat;

        return $this;
    }

    /**
     * @return $this|string|null
     */
    public function dateTimeFormat(string $dateTimeFormat = null)
    {
        if (0 === func_num_args()) {
            return $this->dateTimeFormat;
        }

        $this->dateTimeFormat = $dateTimeFormat;

        return $this;
    }

    /**
     * @return $this|string|null
     */
    public function dateIntervalFormat(string $dateIntervalFormat = null)
    {
        if (0 === func_num_args()) {
            return $this->dateIntervalFormat;
        }

        $this->dateIntervalFormat = $dateIntervalFormat;

        return $this;
    }

    /**
     * @return $this|string|null
     */
    public function numberFormat(string $numberFormat = null)
    {
        if (0 === func_num_args()) {
            return $this->numberFormat;
        }

        $this->numberFormat = $numberFormat;

        return $this;
    }

    /**
     * @return $this|string|null
     */
    public function translationDomain(string $translationDomain = null)
    {
        if (0 === func_num_args()) {
            return $this->translationDomain;
        }

        $this->translationDomain = $translationDomain;

        return $this;
    }

    /**
     * @return $this|array|null
     */
    public function disabledActions(array $disabledActions = null)
    {
        if (0 === func_num_args()) {
            return $this->disabledActions;
        }

        $this->disabledActions = $disabledActions;

        return $this;
    }
}
