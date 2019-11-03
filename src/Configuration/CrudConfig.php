<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Configuration;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class CrudConfig
{
    private $entityFqcn;
    private $labelInSingular = 'Undefined';
    private $labelInPlural = 'Undefined';
    private $translationDomain = 'messages';
    private $dateFormat = 'Y-m-d';
    private $timeFormat = 'H:i:s';
    private $dateTimeFormat = 'F j, Y H:i';
    private $dateIntervalFormat = '%%y Year(s) %%m Month(s) %%d Day(s)';
    private $numberFormat;
    private $templates = [
        'layout' => '@EasyAdmin/layout.html.twig',
        'menu' => '@EasyAdmin/menu.html.twig',
        'paginator' => '@EasyAdmin/paginator.html.twig',
        'index' => '@EasyAdmin/index.html.twig',
        'detail' => '@EasyAdmin/detail.html.twig',
        'form' => '@EasyAdmin/form.html.twig',
        'action' => '@EasyAdmin/action.html.twig',
        'exception' => '@EasyAdmin/exception.html.twig',
        'field_array' => '@EasyAdmin/field_array.html.twig',
        'field_association' => '@EasyAdmin/field_association.html.twig',
        'field_avatar' => '@EasyAdmin/field_avatar.html.twig',
        'field_bigint' => '@EasyAdmin/field_bigint.html.twig',
        'field_boolean' => '@EasyAdmin/field_boolean.html.twig',
        'field_country' => '@EasyAdmin/field_country.html.twig',
        'field_date' => '@EasyAdmin/field_date.html.twig',
        'field_datetime' => '@EasyAdmin/field_datetime.html.twig',
        'field_datetimetz' => '@EasyAdmin/field_datetimetz.html.twig',
        'field_decimal' => '@EasyAdmin/field_decimal.html.twig',
        'field_email' => '@EasyAdmin/field_email.html.twig',
        'field_float' => '@EasyAdmin/field_float.html.twig',
        'field_id' => '@EasyAdmin/field_id.html.twig',
        'field_image' => '@EasyAdmin/field_image.html.twig',
        'field_integer' => '@EasyAdmin/field_integer.html.twig',
        'field_raw' => '@EasyAdmin/field_raw.html.twig',
        'field_simple_array' => '@EasyAdmin/field_simple_array.html.twig',
        'field_smallint' => '@EasyAdmin/field_smallint.html.twig',
        'field_string' => '@EasyAdmin/field_string.html.twig',
        'field_tel' => '@EasyAdmin/field_tel.html.twig',
        'field_text' => '@EasyAdmin/field_text.html.twig',
        'field_time' => '@EasyAdmin/field_time.html.twig',
        'field_toggle' => '@EasyAdmin/field_toggle.html.twig',
        'field_url' => '@EasyAdmin/field_url.html.twig',
        'flash_messages' => '@EasyAdmin/flash_messages.html.twig',
        'label_empty' => '@EasyAdmin/label_empty.html.twig',
        'label_inaccessible' => '@EasyAdmin/label_inaccessible.html.twig',
        'label_null' => '@EasyAdmin/label_null.html.twig',
        'label_undefined' => '@EasyAdmin/label_undefined.html.twig',
    ];

    public static function new(): self
    {
        return new self();
    }

    public function getEntityClass(): string
    {
        return $this->entityFqcn;
    }

    public function getLabelInSingular(): string
    {
        return $this->labelInSingular;
    }

    public function getLabelInPlural(): string
    {
        return $this->labelInPlural;
    }

    public function getTranslationDomain(): string
    {
        return $this->translationDomain;
    }

    public function getDateFormat(): string
    {
        return $this->dateFormat;
    }

    public function getTimeFormat(): string
    {
        return $this->timeFormat;
    }

    public function getDateTimeFormat(): string
    {
        return $this->dateTimeFormat;
    }

    public function getDateIntervalFormat(): string
    {
        return $this->dateIntervalFormat;
    }

    public function getNumberFormat(): ?string
    {
        return $this->numberFormat;
    }

    public function getTemplate(string $templateName): string
    {
        return $this->templates[$templateName];
    }

    public function getTemplates(): array
    {
        return $this->templates;
    }

    public function setEntityClass(string $entityFqcn): self
    {
        $this->entityFqcn = $entityFqcn;

        return $this;
    }

    public function setLabelInSingular(string $label): self
    {
        $this->labelInSingular = $label;

        return $this;
    }

    public function setLabelInPlural(string $label): self
    {
        $this->labelInPlural = $label;

        return $this;
    }

    public function setTranslationDomain(string $domain): self
    {
        $this->translationDomain = $domain;

        return $this;
    }

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

    /**
     * Used to override the default template used to render a specific backend part.
     */
    public function setTemplate(string $templateName, string $templatePath): self
    {
        if (!array_key_exists($templateName, $this->templates)) {
            throw new \InvalidArgumentException(sprintf('The "%s" template is not defined in EasyAdmin. Use one of these allowed template names: %s', $templateName, implode(', ', array_keys($this->templates))));
        }

        $this->templates[$templateName] = $templatePath;

        return $this;
    }

    /**
     * It allows to override more than one template at the same time.
     * Format: ['templateName' => 'templatePath', ...]
     *
     * @param array<string> $templateNamesAndPaths
     */
    public function setTemplates(array $templateNamesAndPaths): self
    {
        foreach ($templateNamesAndPaths as $templateName => $templatePath) {
            $this->setTemplate($templateName, $templatePath);
        }

        return $this;
    }
}
