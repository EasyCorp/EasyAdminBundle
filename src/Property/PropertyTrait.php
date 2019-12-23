<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Property;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\AssetDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\PropertyDto;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

trait PropertyTrait
{
    private $type;
    private $name;
    private $label;
    private $formType;
    private $formTypeOptions = [];
    private $sortable;
    private $permission;
    private $textAlign = 'left';
    private $help;
    private $cssClass;
    private $translationParams = [];
    private $templateName;
    private $templatePath;
    private $customTemplateParams = [];
    private $cssFiles = [];
    private $jsFiles = [];
    private $headContents = [];
    private $bodyContents = [];
    private $customOptions;

    private function __construct()
    {
    }

    public static function new(string $name, ?string $label = null): self
    {
        $property = new static();
        $property->name = $name;
        $property->label = $label;

        return $property;
    }

    public function setType(string $type): PropertyInterface
    {
        $this->type = $type;

        return $this;
    }

    public function setFormType(string $formType): PropertyInterface
    {
        $this->formType = $formType;

        return $this;
    }

    public function setFormTypeOptions(array $options): PropertyInterface
    {
        $this->formTypeOptions = $options;

        return $this;
    }

    public function setSortable(bool $isSortable): PropertyInterface
    {
        $this->sortable = $isSortable;

        return $this;
    }

    public function setPermission(string $role): PropertyInterface
    {
        $this->permission = $role;

        return $this;
    }

    public function setTextAlign(string $textAlign): PropertyInterface
    {
        $this->textAlign = $textAlign;

        return $this;
    }

    public function setHelp(string $help): PropertyInterface
    {
        $this->help = $help;

        return $this;
    }

    public function setCssClass(string $cssClass): PropertyInterface
    {
        $this->cssClass = $cssClass;

        return $this;
    }

    public function setTranslationParams(array $params): PropertyInterface
    {
        $this->translationParams = $params;

        return $this;
    }

    public function setTemplatePath(string $path): PropertyInterface
    {
        $this->templatePath = $path;

        return $this;
    }

    public function setTemplateName(string $name): PropertyInterface
    {
        $this->templateName = $name;

        return $this;
    }

    public function setCustomTemplateParams(array $params): PropertyInterface
    {
        $this->customTemplateParams = $params;

        return $this;
    }

    public function addAssets(string ...$assetPaths): PropertyInterface
    {
        $this->assets = $assetPaths;

        return $this;
    }

    public function addCssFiles(string ...$assetPaths): PropertyInterface
    {
        $this->cssFiles = array_merge($this->cssFiles, $assetPaths);

        return $this;
    }

    public function addJsFiles(string ...$assetPaths): PropertyInterface
    {
        $this->jsFiles = array_merge($this->jsFiles, $assetPaths);

        return $this;
    }

    public function addHtmlContentsToHead(string ...$contents): PropertyInterface
    {
        $this->headContents = array_merge($this->headContents, $contents);

        return $this;
    }

    public function addHtmlContentsToBody(string ...$contents): PropertyInterface
    {
        $this->bodyContents = array_merge($this->bodyContents, $contents);

        return $this;
    }

    public function setCustomOptions(OptionsResolver $resolver): void
    {
    }

    public function setDefaultOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefined([
                'name',
                'type',
                'sortable',
                'formType',
                'formTypeOptions',
                'label',
                'permission',
                'textAlign',
                'help',
                'cssClass',
                'templateName',
                'templatePath',
                'customTemplateParams',
                'assets',
            ])
            ->setDefaults([
                'name' => null,
                'type' => null,
                'sortable' => false,
                'formType' => null,
                'formTypeOptions' => [],
                'label' => null,
                'permission' => null,
                'textAlign' => 'left',
                'help' => null,
                'cssClass' => null,
                'templateName' => null,
                'templatePath' => null,
                'customTemplateParams' => [],
                'assets' => [],
            ])
            ->setAllowedTypes('name', ['string'])
            ->setAllowedValues('textAlign', ['center', 'left', 'right'])
            ->setAllowedValues('type', static function ($value) {
                return !empty($value);
            })
            ->setNormalizer('type', static function (Options $options, $value) {
                return str_replace(' ', '-', $value);
            });
    }

    public function getAsDto(): PropertyDto
    {
        // TODO: resolve and validate options

        if (null !== $this->templateName && null !== $this->templatePath) {
            throw new \InvalidArgumentException(sprintf('Properties can only define either the name or the path of their templates, but the "%s" property defines both (remove one of them).', $this->name));
        }

        return new PropertyDto($this->name, $this->type, $this->formType, $this->formTypeOptions, $this->sortable, $this->label, $this->permission, $this->textAlign, $this->help, $this->cssClass, $this->translationParams, $this->templateName, $this->templatePath, $this->customTemplateParams, new AssetDto($this->cssFiles, $this->jsFiles, $this->headContents, $this->bodyContents), []);
    }
}
