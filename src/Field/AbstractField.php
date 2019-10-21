<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field;

use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractField implements FieldInterface
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

    public static function new(string $property, string $label = null)
    {
        $field = new static();
        $field->property = $property;
        $field->label = $label ?? self::humanizeString($property);

        return $field;
    }

    public function addAssets(): array
    {
        return [];
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

    public function formType(string $formType): self
    {
        $this->formType = $formType;

        return $this;
    }

    public function formTypeOptions(array $formTypeOptions): self
    {
        $this->formTypeOptions = $formTypeOptions;

        return $this;
    }

    public function textAlign(string $textAlign): self
    {
        $this->textAlign = $textAlign;

        return $this;
    }

    public function permission(string $role): self
    {
        $this->permission = $role;

        return $this;
    }

    public function help(string $help): self
    {
        $this->help = $help;

        return $this;
    }

    public function cssClass(string $cssClass): self
    {
        $this->cssClass = $cssClass;

        return $this;
    }

    public function template(string $templatePath): self
    {
        $this->templatePath = $templatePath;

        return $this;
    }

    public function templateParams(array $templateParams): self
    {
        $this->templateParams = $templateParams;

        return $this;
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
