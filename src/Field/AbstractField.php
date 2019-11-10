<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\FieldInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractField implements FieldInterface
{
    private $property;
    private $type;
    private $formType;
    private $formTypeOptions = [];
    private $label;
    private $permission;
    private $textAlign;
    private $help;
    private $cssClass;
    private $defaultTemplatePath;
    private $customTemplatePath;
    private $customTemplateParams = [];
    private $assets = [];

    public static function new(string $property, string $label = null)
    {
        $field = new static();
        $field->property = $property;
        $field->label = $label ?? self::humanizeString($property);

        return $field;
    }

    public function addAssets(array $assets): FieldInterface
    {
        $this->assets = $assets;

        return $this;
    }

    public function setCustomOptions(OptionsResolver $resolver): void
    {
    }

    public function setDefaultOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefined([
                'property',
                'type',
                'formType',
                'formTypeOptions',
                'label',
                'permission',
                'textAlign',
                'help',
                'cssClass',
                'templatePath',
                'templateParams',
            ])
            ->setDefaults([
                'property' => null,
                'cssClass' => null,
                'label' => null,
                'permission' => null,
                'textAlign' => 'left',
                'type' => null,
                'help' => null,
                'formTypeOptions' => [],
                'templateParams' => [],
            ])
            ->setAllowedTypes('property', ['string'])
            ->setAllowedValues('textAlign', ['center', 'left', 'right'])
            ->setAllowedValues('type', static function ($value) {
                return !empty($value);
            })
            ->setNormalizer('type', static function (Options $options, $value) {
                return str_replace(' ', '-', $value);
            });
    }

    public function setType(string $type): FieldInterface
    {
        $this->type = $type;

        return $this;
    }

    public function setFormType(string $formType): FieldInterface
    {
        $this->formType = $formType;

        return $this;
    }

    public function setFormTypeOptions(array $options): FieldInterface
    {
        $this->formTypeOptions = $options;

        return $this;
    }

    public function setTextAlign(string $textAlign): FieldInterface
    {
        $this->textAlign = $textAlign;

        return $this;
    }

    public function setPermission(string $role): FieldInterface
    {
        $this->permission = $role;

        return $this;
    }

    public function setHelp(string $help): FieldInterface
    {
        $this->help = $help;

        return $this;
    }

    public function setCssClass(string $cssClass): FieldInterface
    {
        $this->cssClass = $cssClass;

        return $this;
    }

    public function setDefaultTemplatePath(string $path): FieldInterface
    {
        $this->defaultTemplatePath = $path;

        return $this;
    }

    public function setCustomTemplatePath(string $path): FieldInterface
    {
        $this->customTemplatePath = $path;

        return $this;
    }

    public function setCustomTemplateParams(array $params): FieldInterface
    {
        $this->customTemplateParams = $params;

        return $this;
    }

    public function getProperty(): string
    {
        return $this->property;
    }

    public function getLabel(): string
    {
        return $this->label;
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

    public function getTextAlign(): ?string
    {
        return $this->textAlign;
    }

    public function getPermission(): ?string
    {
        return $this->permission;
    }

    public function getHelp(): ?string
    {
        return $this->help;
    }

    public function getCssClass(): ?string
    {
        return $this->cssClass;
    }

    public function getDefaultTemplatePath(): string
    {
        return $this->defaultTemplatePath;
    }

    public function getCustomTemplatePath(): ?string
    {
        return $this->customTemplatePath;
    }

    public function getCustomTemplateParams(): array
    {
        return $this->customTemplateParams;
    }

    // copied from Symfony\Component\Form\FormRenderer::humanize()
    // (author: Bernhard Schussek <bschussek@gmail.com>).
    private function humanizeString(string $string): string
    {
        return ucfirst(mb_strtolower(trim(preg_replace(['/([A-Z])/', '/[_\s]+/'], ['_$1', ' '], $string))));
    }

    // this method is defined like a magic method to hide it from autocompletion
    public function __call($method, $arguments)
    {
        if ('getOptionValues' !== $method) {
            throw new \BadMethodCallException(sprintf('Call to undefined method %s::%s()', \get_class($this), $method));
        }

        if (count($arguments) > 1 || !is_array($arguments[0])) {
            throw new \BadMethodCallException(sprintf('%s::%s() requires only one argument of type array', \get_class($this), $method));
        }

        $propertyValues = [];
        foreach ($arguments[0] as $propertyName) {
            $propertyValues[$propertyName] = $this->{$propertyName};
        }

        return $propertyValues;
    }
}
