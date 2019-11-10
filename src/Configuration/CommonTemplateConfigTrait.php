<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Configuration;

trait CommonTemplateConfigTrait
{
    private $customTemplates = [];
    private $defaultTemplates = [
        'layout' => '@EasyAdmin/layout.html.twig',
        'menu' => '@EasyAdmin/default/menu.html.twig',
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
        'flash_messages' => '@EasyAdmin/default/flash_messages.html.twig',
        'label_empty' => '@EasyAdmin/label_empty.html.twig',
        'label_inaccessible' => '@EasyAdmin/label_inaccessible.html.twig',
        'label_null' => '@EasyAdmin/label_null.html.twig',
        'label_undefined' => '@EasyAdmin/label_undefined.html.twig',
    ];

    /**
     * @return string|string[]|null
     */
    public function getCustomTemplate(string $templateName = null)
    {
        if (null === $templateName) {
            return $this->customTemplates;
        }

        return $this->customTemplates[$templateName] ?? null;
    }

    /**
     * @return string|string[]|null
     */
    public function getDefaultTemplate(string $templateName = null)
    {
        if (null === $templateName) {
            return $this->defaultTemplates;
        }

        return $this->defaultTemplates[$templateName] ?? null;
    }

    /**
     * Used to override the default template used to render a specific backend part.
     */
    public function setCustomTemplate(string $templateName, string $templatePath): self
    {
        if (!array_key_exists($templateName, $this->defaultTemplates)) {
            throw new \InvalidArgumentException(sprintf('The "%s" template is not defined in EasyAdmin. Use one of these allowed template names: %s', $templateName, implode(', ', array_keys($this->defaultTemplates))));
        }

        $this->customTemplates[$templateName] = $templatePath;

        return $this;
    }

    /**
     * It allows to override more than one template at the same time.
     * Format: ['templateName' => 'templatePath', ...]
     */
    public function setCustomTemplates(array $templateNamesAndPaths): self
    {
        foreach ($templateNamesAndPaths as $templateName => $templatePath) {
            $this->setCustomTemplate($templateName, $templatePath);
        }

        return $this;
    }
}
