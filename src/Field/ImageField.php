<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\FileUploadType;

final class ImageField implements FieldInterface
{
    use FieldTrait;

    public const OPTION_BASE_PATH = 'basePath';

    public function __construct()
    {
        $this
            ->setType('image')
            ->setFormType(FileUploadType::class)
            ->setTextAlign('center')
            ->setTemplateName('crud/field/image')
            ->setCustomOption(self::OPTION_BASE_PATH, null);
    }

    public function setBasePath(string $path): self
    {
        $this->setCustomOption(self::OPTION_BASE_PATH, $path);

        return $this;
    }
}
