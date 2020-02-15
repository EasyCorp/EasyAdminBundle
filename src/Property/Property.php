<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Property;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfigInterface;

final class Property implements PropertyConfigInterface
{
    use PropertyConfigTrait;

    /**
     * This method transforms the current object into any other object that implements
     * PropertyConfigInterface. It's needed when using autoconfigurable properties, where
     * the user gives a Property instance but the application needs TextProperty, etc.
     */
    public function transformInto(string $propertyFqcn): PropertyConfigInterface
    {
        /**
         * @var PropertyConfigInterface
         */
        $newPropertyConfig = $propertyFqcn::new($this->getName());

        $newPropertyConfig->setValue($this->getValue());
        $newPropertyConfig->setFormattedValue($this->getFormattedValue());
        $newPropertyConfig->setVirtual($this->isVirtual());
        $newPropertyConfig->setTextAlign($this->getTextAlign());
        $newPropertyConfig->setTranslationParams($this->getTranslationParams());
        $newPropertyConfig->addCssFiles(...$this->getCssFiles());
        $newPropertyConfig->addJsFiles(...$this->getJsFiles());
        $newPropertyConfig->addHtmlContentsToHead(...$this->getHeadContents());
        $newPropertyConfig->addHtmlContentsToBody(...$this->getBodyContents());
        $newPropertyConfig->setCustomOptions($this->getCustomOptions()->all());

        if (null !== $this->getLabel()) {
            $newPropertyConfig->setLabel($this->getLabel());
        }

        if (null !== $this->isSortable()) {
            $newPropertyConfig->setSortable($this->isSortable());
        }

        if (null !== $this->getPermission()) {
            $newPropertyConfig->setPermission($this->getPermission());
        }

        if (null !== $this->getHelp()) {
            $newPropertyConfig->setHelp($this->getHelp());
        }

        if (null !== $this->getCssClass()) {
            $newPropertyConfig->setCssClass($this->getCssClass());
        }

        if (null !== $this->getFormType()) {
            $newPropertyConfig->setFormType($this->getFormType());
        }

        if (null !== $this->getTemplateName()) {
            $newPropertyConfig->setTemplateName($this->getTemplateName());
        }

        if (null !== $this->getTemplatePath()) {
            $newPropertyConfig->setTemplatePath($this->getTemplatePath());
        }

        return $newPropertyConfig;
    }
}
