<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;

final class Field implements FieldInterface
{
    use FieldTrait;

    /**
     * This method transforms the current object into any other object that implements
     * FieldInterface. It's needed when using autoconfigurable properties, where
     * the user gives a Property instance but the application needs TextProperty, etc.
     */
    public function transformInto(string $fieldFqcn): FieldInterface
    {
        /**
         * @var FieldInterface
         */
        $newField = $fieldFqcn::new($this->getProperty());

        $newField->setValue($this->getValue());
        $newField->setFormattedValue($this->getFormattedValue());
        $newField->setVirtual($this->isVirtual());
        $newField->setTranslationParameters($this->getTranslationParameters());
        $newField->addCssFiles(...$this->getCssFiles());
        $newField->addJsFiles(...$this->getJsFiles());
        $newField->addHtmlContentsToHead(...$this->getHeadContents());
        $newField->addHtmlContentsToBody(...$this->getBodyContents());
        $newField->setCustomOptions($this->getCustomOptions()->all());

        if (null !== $this->getLabel()) {
            $newField->setLabel($this->getLabel());
        }

        if (null !== $this->getTextAlign()) {
            $newField->setTextAlign($this->getTextAlign());
        }

        if (null !== $this->isSortable()) {
            $newField->setSortable($this->isSortable());
        }

        if (null !== $this->getPermission()) {
            $newField->setPermission($this->getPermission());
        }

        if (null !== $this->getHelp()) {
            $newField->setHelp($this->getHelp());
        }

        if (null !== $this->getCssClass()) {
            $newField->setCssClass($this->getCssClass());
        }

        if (null !== $this->getFormType()) {
            $newField->setFormType($this->getFormType());
        }

        if (null !== $this->getTemplateName()) {
            $newField->setTemplateName($this->getTemplateName());
        }

        if (null !== $this->getTemplatePath()) {
            $newField->setTemplatePath($this->getTemplatePath());
        }

        return $newField;
    }
}
