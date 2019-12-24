<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Property;

use EasyCorp\Bundle\EasyAdminBundle\Context\ApplicationContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfigInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\PropertyDto;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\CodeEditorType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CodeEditorProperty implements PropertyConfigInterface
{
    use PropertyConfigTrait;

    private $height;
    private $language;
    private $tabSize;
    private $indentWithTabs;

    public function __construct()
    {
        $this->type = 'code_editor';
        $this->formType = CodeEditorType::class;
        $this->templateName = 'property/code_editor';
        $this->cssFiles = ['bundles/easyadmin/form-type-code-editor.css'];
        $this->jsFiles = ['bundles/easyadmin/form-type-code-editor.js'];
        $this->customOptions = [
            ['name' => 'height', 'types' => [null, 'int'], 'default' => null],
            ['name' => 'language', 'types' => ['string'], 'default' => 'markdown'],
            ['name' => 'tabSize', 'types' => [null, 'int'], 'default' => 4],
            ['name' => 'indentWithTabs', 'types' => ['bool'], 'default' => false],
        ];
    }

    public static function getCustomOptions(): array
    {
        return ['height', 'language', 'tabSize', 'indentWithTabs'];
    }

    public function configureCustomOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefined('height')
            ->setAllowedTypes('height', ['null', 'int'])
            ->setDefault('height', null)

            ->setDefined('language')
            ->setAllowedTypes('language', 'string')
            ->setDefault('language', 'markdown')
            ->setAllowedValues('language', ['css', 'dockerfile', 'js', 'markdown', 'nginx', 'php', 'shell', 'sql', 'twig', 'xml', 'yaml-frontmatter', 'yaml'])

            ->setDefined('tabSize')
            ->setAllowedTypes('tabSize', ['integer', 'null'])
            ->setDefault('tabSize', 4)

            ->setDefined('indentWithTabs')
            ->setAllowedTypes('indentWithTabs', ['boolean', 'null'])
            ->setDefault('indentWithTabs', false)
        ;
    }

    public function setHeight(int $heightInPixels): self
    {
        $this->height = $heightInPixels;

        return $this;
    }

    public function setLanguage(string $language): self
    {
        $this->language = $language;

        return $this;
    }

    public function setTabSize(int $tabSize): self
    {
        $this->tabSize = $tabSize;

        return $this;
    }

    public function setIndentWithTabs(bool $useTabs): self
    {
        $this->indentWithTabs = $useTabs;

        return $this;
    }

    public function validate(): array
    {
        $resolver = new OptionsResolver();
        $this->configureCustomOptions($resolver);

        $customOptionValues = [];
        foreach ($this->customOptions as $option) {
            $customOptionValues[$option['name']] = $this->{$option['name']};
        }

        return $resolver->resolve($customOptionValues);
    }

    public function build(PropertyDto $propertyDto, EntityDto $entityDto, ApplicationContext $applicationContext): PropertyDto
    {
        $customOptionValues = $this->validate();

        return $propertyDto->with([
            'customOptions' => $customOptionValues,
        ]);
    }
}
