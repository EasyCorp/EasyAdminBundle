<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Config\Asset;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\ReplacedFileBehavior;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\FileUploadType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraint;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class FileField implements FieldInterface
{
    use FieldTrait;

    public const OPTION_BASE_PATH = 'basePath';
    public const OPTION_UPLOAD_DIR = 'uploadDir';
    public const OPTION_UPLOADED_FILE_NAME_PATTERN = 'uploadedFileNamePattern';
    public const OPTION_FILE_CONSTRAINTS = 'fileConstraints';
    public const OPTION_REPLACED_FILE_BEHAVIOR = 'replacedFileBehavior';
    public const OPTION_DELETABLE = 'deletable';
    public const OPTION_VIEWABLE = 'viewable';
    public const OPTION_DOWNLOADABLE = 'downloadable';
    public const OPTION_MIME_TYPES = 'mimeTypes';
    public const OPTION_MIME_TYPES_MESSAGE = 'mimeTypesMessage';
    public const OPTION_MAX_SIZE = 'maxSize';
    public const OPTION_MAX_SIZE_MESSAGE = 'maxSizeMessage';
    public const OPTION_FLYSYSTEM_STORAGE = 'flysystemStorage';
    public const OPTION_FLYSYSTEM_URL_PREFIX = 'flysystemUrlPrefix';

    public static function new(string $propertyName, TranslatableInterface|string|bool|null $label = null): self
    {
        return (new self())
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setTemplateName('crud/field/file')
            ->setFormType(FileUploadType::class)
            ->addCssClass('field-file')
            ->addJsFiles(Asset::fromEasyAdminAssetPackage('field-file-upload.js'))
            ->setDefaultColumns('col-md-7 col-xxl-5')
            ->setCustomOption(self::OPTION_BASE_PATH, null)
            ->setCustomOption(self::OPTION_UPLOAD_DIR, null)
            ->setCustomOption(self::OPTION_UPLOADED_FILE_NAME_PATTERN, '[name].[extension]')
            ->setCustomOption(self::OPTION_FILE_CONSTRAINTS, [])
            ->setCustomOption(self::OPTION_MIME_TYPES, null)
            ->setCustomOption(self::OPTION_REPLACED_FILE_BEHAVIOR, ReplacedFileBehavior::DELETE)
            ->setCustomOption(self::OPTION_VIEWABLE, true)
            ->setCustomOption(self::OPTION_DOWNLOADABLE, true)
            ->setCustomOption(self::OPTION_DELETABLE, true)
            ->setCustomOption(self::OPTION_MIME_TYPES_MESSAGE, null)
            ->setCustomOption(self::OPTION_MAX_SIZE, null)
            ->setCustomOption(self::OPTION_MAX_SIZE_MESSAGE, null)
            ->setCustomOption(self::OPTION_FLYSYSTEM_STORAGE, null)
            ->setCustomOption(self::OPTION_FLYSYSTEM_URL_PREFIX, null);
    }

    /**
     * Sets the path prepended to the file name to build the URL used
     * to display the file in the detail and index pages (e.g. 'uploads/files/').
     */
    public function setBasePath(string $path): self
    {
        $this->setCustomOption(self::OPTION_BASE_PATH, $path);

        return $this;
    }

    /**
     * Relative to project's root directory (e.g. use 'public/uploads/' for `<your-project-dir>/public/uploads/`)
     * Default upload dir: `<your-project-dir>/public/uploads/files/`.
     */
    public function setUploadDir(string $uploadDirPath): self
    {
        $this->setCustomOption(self::OPTION_UPLOAD_DIR, $uploadDirPath);

        return $this;
    }

    /**
     * @param string|\Closure(UploadedFile, object): string $patternOrCallable
     *
     * If it's a string, uploaded files will be renamed according to the given pattern.
     * The pattern can include the following special values:
     *
     *    [DD] [MM] [YYYY] [YY] [hh] [mm] [ss] [timestamp]
     *    [name] [slug] [extension] [contenthash]
     *    [randomhash] [uuid] [ulid]
     *
     *    e.g. [YYYY]/[MM]/[DD]/[slug]-[contenthash].[extension]
     *
     * If it's a callable, you will be passed the UploadedFile instance and the
     * current entity instance, and you must return a string with the new filename
     * (which can include subdirectories). On the NEW page, the entity is a fresh
     * instance (possibly without an ID). On the EDIT page, it has its current DB values.
     * Example:
     *
     *     fn (UploadedFile $file, MyEntity $entity) => sprintf('%s/%s.%s', $entity->getSlug(), $file->getFilename(), $file->guessExtension())
     */
    public function setUploadedFileNamePattern(string|\Closure $patternOrCallable): self
    {
        $this->setCustomOption(self::OPTION_UPLOADED_FILE_NAME_PATTERN, $patternOrCallable);

        return $this;
    }

    /**
     * @param Constraint|array<Constraint> $constraints
     *
     * Define constraints to be validated on the FileType
     */
    public function setFileConstraints(Constraint|array $constraints): self
    {
        if (\is_array($constraints)) {
            foreach ($constraints as $key => $constraint) {
                if (!$constraint instanceof Constraint) {
                    throw new \InvalidArgumentException(sprintf('The "%s" method expects a "%s" instance or an array of them; got "%s" at key "%s".', __METHOD__, Constraint::class, get_debug_type($constraint), $key));
                }
            }
        } else {
            $constraints = [$constraints];
        }

        $this->setCustomOption(self::OPTION_FILE_CONSTRAINTS, $constraints);

        return $this;
    }

    /**
     * Defines the allowed MIME types for this file (by default, all types are accepted).
     *
     * @param string $mimeTypes a comma-separated list of one or more file types.
     *                          You can use any value considered valid in the HTML `accept` attribute
     *                          https://developer.mozilla.org/en-US/docs/Web/HTML/Reference/Attributes/accept
     *                          Examples:
     *
     *     '.pdf'                  (single extension)
     *     '.doc,.docx'            (multiple extensions)
     *     'image/*'               (any image type)
     *     'image/png,image/jpeg'  (specific MIME types)
     *     'video/*'               (any video type)
     *     'audio/*'               (any audio type)
     *     '.pdf,image/*'          (mix of extensions and MIME types)
     * @param string|null $errorMessage Custom error message shown when the MIME type is invalid.
     *                                  Available placeholders:
     *
     *     {{ file }}   // absolute file path
     *     {{ name }}   // base file name
     *     {{ type }}   // the MIME type of the given file
     *     {{ types }}  // the list of allowed MIME types
     */
    public function mimeTypes(string $mimeTypes, ?string $errorMessage = null): self
    {
        $this->setCustomOption(self::OPTION_MIME_TYPES, $mimeTypes);
        $this->setCustomOption(self::OPTION_MIME_TYPES_MESSAGE, $errorMessage);

        return $this;
    }

    /**
     * Sets the maximum allowed size per uploaded file.
     *
     * @param int|string  $maxSize        an integer (bytes) or a suffixed string: `'200k'`, `'2M'`, `'1G'` (SI units) or `'1Ki'`, `'1Mi'` (binary units)
     * @param string|null $maxSizeMessage Custom error message shown when the file exceeds the maximum size.
     *                                    Available placeholders:
     *
     *     {{ file }}    // absolute file path
     *     {{ limit }}   // maximum file size allowed
     *     {{ name }}    // base file name
     *     {{ size }}    // file size of the given file
     *     {{ suffix }}  // suffix for the used file size unit
     */
    public function maxSize(int|string $maxSize, ?string $maxSizeMessage = null): self
    {
        $this->setCustomOption(self::OPTION_MAX_SIZE, $maxSize);
        $this->setCustomOption(self::OPTION_MAX_SIZE_MESSAGE, $maxSizeMessage);

        return $this;
    }

    /**
     * When a file is replaced by uploading a new one, the old file is deleted
     * from the filesystem (this is the default behavior).
     */
    public function deleteReplacedFile(): self
    {
        $this->setCustomOption(self::OPTION_REPLACED_FILE_BEHAVIOR, ReplacedFileBehavior::DELETE);

        return $this;
    }

    /**
     * When a file is replaced by uploading a new one, the old file is kept
     * in the filesystem (renamed to avoid collisions).
     */
    public function keepReplacedFile(): self
    {
        $this->setCustomOption(self::OPTION_REPLACED_FILE_BEHAVIOR, ReplacedFileBehavior::KEEP);

        return $this;
    }

    /**
     * When a file is replaced by uploading a new one, the old file is kept
     * and an exception is thrown if the new file name conflicts with an existing one.
     */
    public function keepReplacedFileOrFail(): self
    {
        $this->setCustomOption(self::OPTION_REPLACED_FILE_BEHAVIOR, ReplacedFileBehavior::KEEP_OR_FAIL);

        return $this;
    }

    /**
     * If true (default), a link to view the file is displayed next to the form field.
     */
    public function isViewable(bool $isViewable = true): self
    {
        $this->setCustomOption(self::OPTION_VIEWABLE, $isViewable);

        return $this;
    }

    /**
     * If true (default), a link to download the file is displayed next to the form field.
     */
    public function isDownloadable(bool $isDownloadable = true): self
    {
        $this->setCustomOption(self::OPTION_DOWNLOADABLE, $isDownloadable);

        return $this;
    }

    /**
     * If true (default), a button to delete the file is displayed next to the form field.
     */
    public function isDeletable(bool $isDeletable = true): self
    {
        $this->setCustomOption(self::OPTION_DELETABLE, $isDeletable);

        return $this;
    }

    /**
     * Sets the Flysystem storage service ID to use for uploading/deleting files
     * (e.g. 'default.storage' as registered by league/flysystem-bundle).
     */
    public function setFlysystemStorage(string $storageName): self
    {
        $this->setCustomOption(self::OPTION_FLYSYSTEM_STORAGE, $storageName);

        return $this;
    }

    /**
     * Sets the URL prefix used to generate public URLs for files stored in Flysystem
     * (e.g. 'https://cdn.example.com/uploads').
     *
     * This is optional. When not set, EasyAdmin derives the public URL from the
     * Flysystem storage itself (via the 'public_url' or 'public_url_generator'
     * configured for that storage in flysystem-bundle). Use this setter to
     * override that default, for example when the admin UI serves files from a
     * different host than the one configured in Flysystem.
     */
    public function setFlysystemUrlPrefix(string $urlPrefix): self
    {
        $this->setCustomOption(self::OPTION_FLYSYSTEM_URL_PREFIX, $urlPrefix);

        return $this;
    }
}
