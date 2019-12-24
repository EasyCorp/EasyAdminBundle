<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfigInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @internal
 */
final class FieldView
{
    private $property;
    private $type;
    private $formType;
    private $formTypeOptions;
    private $label;
    private $permission;
    private $textAlign;
    private $help;
    private $cssClass;
    private $templatePath;
    private $templateParams;

    public function __construct(PropertyConfigInterface $field)
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver, $field);

        $fieldOptionValues = $field->getOptionValues($resolver->getDefinedOptions());
        // needed to remove NULL options (otherwise the default values are not applied)
        $fieldOptionValues = array_filter($fieldOptionValues);
        $validatedFieldOptionValues = $resolver->resolve($fieldOptionValues);

        foreach ($validatedFieldOptionValues as $optionName => $optionValue) {
            $this->{$optionName} = $optionValue;
        }
    }

    public function getProperty(): string
    {
        return $this->property;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getFormType(): string
    {
        return $this->formType;
    }

    public function getFormTypeOptions(): array
    {
        return $this->formTypeOptions;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getPermission(): ?string
    {
        return $this->permission;
    }

    public function getTextAlign(): string
    {
        return $this->textAlign;
    }

    public function getHelp(): ?string
    {
        return $this->help;
    }

    public function getCssClass(): ?string
    {
        return $this->cssClass;
    }

    public function getTemplatePath(): string
    {
        return $this->templatePath;
    }

    public function getTemplateParams(): array
    {
        return $this->templateParams;
    }

    private function configureOptions(OptionsResolver $resolver, PropertyConfigInterface $field): void
    {
        $field->setDefaultOptions($resolver);
        $field->setCustomOptions($resolver);
    }
}
