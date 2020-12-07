<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Config\Option\TextAlign;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\FileUploadType;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class ImageField implements FieldInterface
{
    use FieldTrait;

    public const OPTION_BASE_PATH = 'basePath';
    public const OPTION_UPLOAD_DIR = 'uploadDir';
    public const OPTION_UPLOADED_FILE_NAME_PATTERN = 'uploadedFileNamePattern';

    public static function new(string $propertyName, ?string $label = null): self
    {
        return (new self())
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setTemplateName('crud/field/image')
            ->setFormType(FileUploadType::class)
            ->addCssClass('field-image')
            ->setTextAlign(TextAlign::CENTER)
            ->setCustomOption(self::OPTION_BASE_PATH, null)
            ->setCustomOption(self::OPTION_UPLOAD_DIR, null)
            ->setCustomOption(self::OPTION_UPLOADED_FILE_NAME_PATTERN, '[name].[extension]');
    }

    public function setBasePath(string $path): self
    {
        $this->setCustomOption(self::OPTION_BASE_PATH, $path);

        return $this;
    }

    /**
     * Relative to project's root directory (e.g. use 'public/uploads/' for `<your-project-dir>/public/uploads/`)
     * Default upload dir: `<your-project-dir>/public/uploads/images/`.
     */
    public function setUploadDir(string $uploadDirPath): self
    {
        $this->setCustomOption(self::OPTION_UPLOAD_DIR, $uploadDirPath);

        return $this;
    }

    /**
     * @param string|\Closure $patternOrCallable
     *
     * If it's a string, uploaded files will be renamed according to the given pattern.
     * The pattern can include the following special values:
     *   [day] [month] [year] [timestamp]
     *   [name] [slug] [extension] [contenthash]
     *   [randomhash] [uuid] [ulid]
     * (e.g. [year]/[month]/[day]/[slug]-[contenthash].[extension])
     *
     * If it's a callable, you will be passed the Symfony's UploadedFile instance and you must
     * return a string with the new filename.
     * (e.g. fn(UploadedFile $file) => sprintf('upload_%d_%s.%s', random_int(1, 999), $file->getFilename(), $file->guessExtension()))
     */
    public function setUploadedFileNamePattern($patternOrCallable): self
    {
        $this->setCustomOption(self::OPTION_UPLOADED_FILE_NAME_PATTERN, $patternOrCallable);

        return $this;
    }
}
