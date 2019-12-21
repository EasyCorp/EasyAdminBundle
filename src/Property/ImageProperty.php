<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Property;

use EasyCorp\Bundle\EasyAdminBundle\Context\ApplicationContext;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\PropertyDto;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\FileUploadType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ImageProperty extends AbstractProperty
{
    private $basePath;

    public function __construct()
    {
        $this->type = 'image';
        $this->formType = FileUploadType::class;
        $this->textAlign = 'center';
        $this->templateName = 'property/image';
    }

    public function setCustomOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefined('basePath')
            ->setAllowedTypes('basePath', 'string')
            ->setDefault('basePath', null);
    }

    public function setBasePath(string $path): self
    {
        $this->basePath = $path;

        return $this;
    }

    public function build(PropertyDto $propertyDto, EntityDto $entityDto, ApplicationContext $applicationContext): PropertyDto
    {
        $formattedValue = $this->getImagePath($propertyDto->getValue(), $this->basePath);

        // this check is needed to avoid displaying broken images when image properties are optional
        if (empty($formattedValue) || $formattedValue === rtrim($this->basePath ?? '', '/')) {
            return $propertyDto->with([
                'templateName' => 'label/empty',
            ]);
        }

        return $propertyDto->with([
            'customOptions' => [
                'basePath' => $this->basePath,
            ],
            'formattedValue' => $formattedValue,
        ]);
    }

    private function getImagePath(?string $imagePath, ?string $basePath): ?string
    {
        // add the base path only to images that are not absolute URLs (http or https) or protocol-relative URLs (//)
        if (null === $imagePath || 0 !== preg_match('/^(http[s]?|\/\/)/i', $imagePath)) {
            return $imagePath;
        }

        return isset($basePath)
            ? rtrim($basePath, '/').'/'.ltrim($imagePath, '/')
            : '/'.ltrim($imagePath, '/');
    }
}
