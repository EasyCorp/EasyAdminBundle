<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dashboard;

use Symfony\Component\OptionsResolver\OptionsResolver;

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
    private static $defaultValues = [
        'siteName' => 'EasyAdmin',
        'dateFormat' => 'Y-m-d',
        'timeFormat' => 'H:i:s',
        'dateTimeFormat' => 'F j, Y H:i',
        'dateIntervalFormat' => '%%y Year(s) %%m Month(s) %%d Day(s)',
        'numberFormat' => '',
        'translationDomain' => 'messages',
        'disabledActions' => [],
    ];

    private $siteName;
    private $dateFormat;
    private $timeFormat;
    private $dateTimeFormat;
    private $numberFormat;
    private $translationDomain;
    private $disabledActions;

    public static function new(): self
    {
        return new self();
    }

    public function siteName(string $name): self
    {
        if (0 === func_num_args()) {
            return $this->siteName;
        }

        $this->siteName = $name;

        return $this;
    }

    public function dateFormat(string $dateFormat): self
    {
        if (0 === func_num_args()) {
            return $this->dateFormat;
        }

        $this->dateFormat = $dateFormat;

        return $this;
    }

    public function timeFormat(string $timeFormat): self
    {
        if (0 === func_num_args()) {
            return $this->timeFormat;
        }

        $this->timeFormat = $timeFormat;

        return $this;
    }

    public function dateTimeFormat(string $dateTimeFormat): self
    {
        if (0 === func_num_args()) {
            return $this->dateTimeFormat;
        }

        $this->dateTimeFormat = $dateTimeFormat;

        return $this;
    }

    public function numberFormat(string $numberFormat): self
    {
        if (0 === func_num_args()) {
            return $this->numberFormat;
        }

        $this->numberFormat = $numberFormat;

        return $this;
    }

    public function translationDomain(string $translationDomain): self
    {
        if (0 === func_num_args()) {
            return $this->translationDomain;
        }

        $this->translationDomain = $translationDomain;

        return $this;
    }

    public function disabledActions(array $disabledActions): self
    {
        if (0 === func_num_args()) {
            return $this->disabledActions;
        }

        $this->disabledActions = $disabledActions;

        return $this;
    }

    public function validateConfig(): void
    {
        $optionsResolver = new OptionsResolver();
        $optionsResolver->setDefaults(self::$defaultValues);

        $options = [
            'siteName' => $this->siteName,
            'dateFormat' => $this->dateFormat,
            'timeFormat' => $this->timeFormat,
            'dateTimeFormat' => $this->dateTimeFormat,
            'numberFormat' => $this->numberFormat,
            'translationDomain' => $this->translationDomain,
            'disabledActions' => $this->disabledActions,
        ];

        $optionsResolver->resolve($options);
    }
}
