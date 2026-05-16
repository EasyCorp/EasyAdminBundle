<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\Model\FlysystemFile;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToGeneratePublicUrl;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Mime\MimeTypes;
use Symfony\Component\Validator\Constraints\File as FileConstraint;
use function Symfony\Component\String\u;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final readonly class ImageConfigurator implements FieldConfiguratorInterface
{
    public function __construct(
        private string $projectDir,
        private ?ContainerInterface $flysystemLocator = null,
    ) {
    }

    public function supports(FieldDto $field, EntityDto $entityDto): bool
    {
        return ImageField::class === $field->getFieldFqcn();
    }

    public function configure(FieldDto $field, EntityDto $entityDto, AdminContext $context): void
    {
        $flysystemStorageName = $field->getCustomOption(ImageField::OPTION_FLYSYSTEM_STORAGE);
        $flysystemUrlPrefix = $field->getCustomOption(ImageField::OPTION_FLYSYSTEM_URL_PREFIX);
        $filesystem = null;

        if (null !== $flysystemStorageName) {
            $filesystem = $this->resolveFlysystemStorage($flysystemStorageName);
        }

        $configuredBasePath = $field->getCustomOption(ImageField::OPTION_BASE_PATH);

        if (null !== $filesystem) {
            $formattedValue = \is_array($field->getValue())
                ? $this->getFlysystemImagesPaths($field->getValue(), $filesystem, $flysystemUrlPrefix, $field->getProperty())
                : $this->getFlysystemImagePath($field->getValue(), $filesystem, $flysystemUrlPrefix, $field->getProperty());
        } else {
            $formattedValue = \is_array($field->getValue())
                ? $this->getImagesPaths($field->getValue(), $configuredBasePath)
                : $this->getImagePath($field->getValue(), $configuredBasePath);
        }
        $field->setFormattedValue($formattedValue);

        $field->setFormTypeOption('upload_filename', $field->getCustomOption(ImageField::OPTION_UPLOADED_FILE_NAME_PATTERN));

        // this check is needed to avoid displaying broken images when image properties are optional
        if (null === $formattedValue || '' === $formattedValue || (\is_array($formattedValue) && 0 === \count($formattedValue)) || $formattedValue === rtrim($configuredBasePath ?? '', '/')) {
            $field->setTemplateName('label/empty');
        }

        if (!\in_array($context->getCrud()->getCurrentPage(), [Crud::PAGE_EDIT, Crud::PAGE_NEW], true)) {
            return;
        }

        $relativeUploadDir = $field->getCustomOption(ImageField::OPTION_UPLOAD_DIR);
        if (null === $relativeUploadDir) {
            throw new \InvalidArgumentException(sprintf('The "%s" image field must define the directory where the images are uploaded using the setUploadDir() method.', $field->getProperty()));
        }

        if (null !== $filesystem) {
            // For Flysystem, use the upload dir as-is (it's a Flysystem path, not a local path)
            $relativeUploadDir = u($relativeUploadDir)->trimStart('/')->ensureEnd('/')->toString();
            $field->setFormTypeOption('upload_dir', $relativeUploadDir);
            $field->setFormTypeOption('flysystem_storage', $filesystem);
            $field->setFormTypeOption('flysystem_url_prefix', $flysystemUrlPrefix);

            // Override upload callables to use Flysystem
            $field->setFormTypeOption('upload_new', static function (UploadedFile $file, string $uploadDir, string $fileName) use ($filesystem) {
                $path = rtrim($uploadDir, '/').'/'.$fileName;
                $stream = fopen($file->getPathname(), 'r');
                try {
                    $filesystem->writeStream($path, $stream);
                } finally {
                    if (\is_resource($stream)) {
                        fclose($stream);
                    }
                }
            });

            $field->setFormTypeOption('upload_delete', static function (FlysystemFile|File $file) use ($filesystem) {
                $path = $file instanceof FlysystemFile ? $file->getPathname() : $file->getFilename();
                try {
                    $filesystem->delete($path);
                } catch (\Throwable) {
                    // ignore delete errors for remote storage
                }
            });

            $field->setFormTypeOption('upload_validate', static function (string $filename) use ($filesystem) {
                if (!$filesystem->fileExists($filename)) {
                    return $filename;
                }

                $index = 1;
                $pathInfo = pathinfo($filename);
                $dir = '.' === $pathInfo['dirname'] ? '' : $pathInfo['dirname'].'/';
                while ($filesystem->fileExists($filename = sprintf('%s%s_%d.%s', $dir, $pathInfo['filename'], $index, $pathInfo['extension']))) {
                    ++$index;
                }

                return $filename;
            });

            // Disable download_path for Flysystem (URLs are built via flysystem_url_prefix)
            $field->setFormTypeOption('download_path', null);
        } else {
            $relativeUploadDir = u($relativeUploadDir)->trimStart(\DIRECTORY_SEPARATOR)->ensureEnd(\DIRECTORY_SEPARATOR)->toString();
            $isStreamWrapper = filter_var($relativeUploadDir, \FILTER_VALIDATE_URL);
            if (false !== $isStreamWrapper) {
                $absoluteUploadDir = $relativeUploadDir;
            } else {
                $absoluteUploadDir = u($relativeUploadDir)->ensureStart($this->projectDir.\DIRECTORY_SEPARATOR)->toString();
            }
            $field->setFormTypeOption('upload_dir', $absoluteUploadDir);
        }

        $mimeTypes = $field->getCustomOption(ImageField::OPTION_MIME_TYPES);
        if (null !== $mimeTypes) {
            $field->setFormTypeOption('attr.accept', $mimeTypes);

            $processedMimeTypes = [];
            foreach (explode(',', $mimeTypes) as $token) {
                $token = trim($token);
                if (str_starts_with($token, '.')) {
                    $processedMimeTypes = array_merge($processedMimeTypes, MimeTypes::getDefault()->getMimeTypes(ltrim($token, '.')));
                } else {
                    $processedMimeTypes[] = $token;
                }
            }

            if ([] !== $processedMimeTypes) {
                $constraints = $field->getCustomOption(ImageField::OPTION_FILE_CONSTRAINTS) ?? [];
                if (!\is_array($constraints)) {
                    $constraints = [$constraints];
                }
                $mimeTypesMessage = $field->getCustomOption(ImageField::OPTION_MIME_TYPES_MESSAGE);
                $constraints[] = new FileConstraint(mimeTypes: $processedMimeTypes, mimeTypesMessage: $mimeTypesMessage);
                $field->setCustomOption(ImageField::OPTION_FILE_CONSTRAINTS, $constraints);
            }
        }

        $maxSize = $field->getCustomOption(ImageField::OPTION_MAX_SIZE);
        if (null !== $maxSize) {
            $constraints = $field->getCustomOption(ImageField::OPTION_FILE_CONSTRAINTS) ?? [];
            if (!\is_array($constraints)) {
                $constraints = [$constraints];
            }
            $maxSizeMessage = $field->getCustomOption(ImageField::OPTION_MAX_SIZE_MESSAGE);
            $constraints[] = new FileConstraint(maxSize: $maxSize, maxSizeMessage: $maxSizeMessage);
            $field->setCustomOption(ImageField::OPTION_FILE_CONSTRAINTS, $constraints);
        }

        $field->setFormTypeOption('file_constraints', $field->getCustomOption(ImageField::OPTION_FILE_CONSTRAINTS));
        $field->setFormTypeOption('replaced_file_behavior', $field->getCustomOption(ImageField::OPTION_REPLACED_FILE_BEHAVIOR));
        $field->setFormTypeOption('allow_delete', $field->getCustomOption(ImageField::OPTION_DELETABLE));
        $field->setFormTypeOption('allow_view', $field->getCustomOption(ImageField::OPTION_VIEWABLE));
        $field->setFormTypeOption('allow_download', $field->getCustomOption(ImageField::OPTION_DOWNLOADABLE));
    }

    private function resolveFlysystemStorage(string $storageName): FilesystemOperator
    {
        if (!interface_exists(FilesystemOperator::class)) {
            throw new \LogicException(sprintf('You configured Flysystem storage "%s" but the "league/flysystem-bundle" package is not installed. Run "composer require league/flysystem-bundle".', $storageName));
        }

        if (null === $this->flysystemLocator || !$this->flysystemLocator->has($storageName)) {
            throw new \InvalidArgumentException(sprintf('The Flysystem storage "%s" is not configured. Make sure the service exists and implements "%s".', $storageName, FilesystemOperator::class));
        }

        return $this->flysystemLocator->get($storageName);
    }

    /**
     * @param array<string|null>|null $images
     *
     * @return array<string|null>
     */
    private function getImagesPaths(?array $images, ?string $basePath): array
    {
        $imagesPaths = [];
        foreach ($images as $image) {
            $imagesPaths[] = $this->getImagePath($image, $basePath);
        }

        return $imagesPaths;
    }

    private function getImagePath(?string $imagePath, ?string $basePath): ?string
    {
        // add the base path only to images that are not absolute URLs (http or https) or protocol-relative URLs (//)
        if (null === $imagePath || 0 !== preg_match('/^(http[s]?|\/\/)/i', $imagePath)) {
            return $imagePath;
        }

        // remove project path from filepath
        $imagePath = str_replace($this->projectDir.\DIRECTORY_SEPARATOR.'public'.\DIRECTORY_SEPARATOR, '', $imagePath);

        return isset($basePath)
            ? rtrim($basePath, '/').'/'.ltrim($imagePath, '/')
            : '/'.ltrim($imagePath, '/');
    }

    /**
     * @param array<string|null>|null $images
     *
     * @return array<string|null>
     */
    private function getFlysystemImagesPaths(?array $images, FilesystemOperator $filesystem, ?string $urlPrefix, string $propertyName): array
    {
        $imagesPaths = [];
        foreach ($images as $image) {
            $imagesPaths[] = $this->getFlysystemImagePath($image, $filesystem, $urlPrefix, $propertyName);
        }

        return $imagesPaths;
    }

    private function getFlysystemImagePath(?string $imagePath, FilesystemOperator $filesystem, ?string $urlPrefix, string $propertyName): ?string
    {
        if (null === $imagePath) {
            return null;
        }

        // If it's already an absolute URL, return as-is
        if (0 !== preg_match('/^(http[s]?|\/\/)/i', $imagePath)) {
            return $imagePath;
        }

        if (null !== $urlPrefix) {
            return rtrim($urlPrefix, '/').'/'.ltrim($imagePath, '/');
        }

        try {
            return $filesystem->publicUrl(ltrim($imagePath, '/'));
        } catch (UnableToGeneratePublicUrl $e) {
            throw new \LogicException(sprintf('Unable to generate the public URL for the image stored in Flysystem for the "%s" field. Either configure the "public_url" option for this storage in your Flysystem configuration, or call setFlysystemUrlPrefix() on this field.', $propertyName), 0, $e);
        }
    }
}
