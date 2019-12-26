<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Property;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfigInterface;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\FileUploadType;

class ImageProperty implements PropertyConfigInterface
{
    use PropertyConfigTrait;

    public const OPTION_BASE_PATH = 'basePath';

    public function __construct()
    {
        $this
            ->setType('image')
            ->setFormType(FileUploadType::class)
            ->setTextAlign('center')
            ->setTemplateName('property/image')
            ->setCustomOption(self::OPTION_BASE_PATH, null);
    }

    public function setBasePath(string $path): self
    {
        $this->basePath = $path;

        return $this;
    }
}
