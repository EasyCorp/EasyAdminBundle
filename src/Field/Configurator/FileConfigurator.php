<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\FileField;
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
final readonly class FileConfigurator implements FieldConfiguratorInterface
{
    public function __construct(
        private string $projectDir,
        private ?ContainerInterface $flysystemLocator = null,
    ) {
    }

    public function supports(FieldDto $field, EntityDto $entityDto): bool
    {
        return FileField::class === $field->getFieldFqcn();
    }

    public function configure(FieldDto $field, EntityDto $entityDto, AdminContext $context): void
    {
        $flysystemStorageName = $field->getCustomOption(FileField::OPTION_FLYSYSTEM_STORAGE);
        $flysystemUrlPrefix = $field->getCustomOption(FileField::OPTION_FLYSYSTEM_URL_PREFIX);
        $filesystem = null;

        if (null !== $flysystemStorageName) {
            $filesystem = $this->resolveFlysystemStorage($flysystemStorageName);
        }

        $configuredBasePath = $field->getCustomOption(FileField::OPTION_BASE_PATH);

        if (null !== $filesystem) {
            $formattedValue = \is_array($field->getValue())
                ? $this->getFlysystemFilesPaths($field->getValue(), $filesystem, $flysystemUrlPrefix, $field->getProperty())
                : $this->getFlysystemFilePath($field->getValue(), $filesystem, $flysystemUrlPrefix, $field->getProperty());
        } else {
            $formattedValue = \is_array($field->getValue())
                ? $this->getFilesPaths($field->getValue(), $configuredBasePath)
                : $this->getFilePath($field->getValue(), $configuredBasePath);
        }
        $field->setFormattedValue($formattedValue);

        $pattern = $field->getCustomOption(FileField::OPTION_UPLOADED_FILE_NAME_PATTERN);

        if (\is_callable($pattern)) {
            $entityInstance = $entityDto->getInstance();
            $originalCallback = $pattern;
            $pattern = static function (UploadedFile $file) use ($originalCallback, $entityInstance) {
                return $originalCallback($file, $entityInstance);
            };
        }

        $field->setFormTypeOption('upload_filename', $pattern);

        // this check is needed to avoid displaying broken links when file properties are optional
        if (null === $formattedValue || '' === $formattedValue || (\is_array($formattedValue) && 0 === \count($formattedValue)) || $formattedValue === rtrim($configuredBasePath ?? '', '/')) {
            $field->setTemplateName('label/empty');
        }

        if (!\in_array($context->getCrud()->getCurrentPage(), [Crud::PAGE_EDIT, Crud::PAGE_NEW], true)) {
            return;
        }

        $relativeUploadDir = $field->getCustomOption(FileField::OPTION_UPLOAD_DIR);
        if (null === $relativeUploadDir) {
            throw new \InvalidArgumentException(sprintf('The "%s" file field must define the directory where the files are uploaded using the setUploadDir() method.', $field->getProperty()));
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

        $mimeTypes = $field->getCustomOption(FileField::OPTION_MIME_TYPES);
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
                $constraints = $field->getCustomOption(FileField::OPTION_FILE_CONSTRAINTS) ?? [];
                if (!\is_array($constraints)) {
                    $constraints = [$constraints];
                }
                $mimeTypesMessage = $field->getCustomOption(FileField::OPTION_MIME_TYPES_MESSAGE);
                $constraints[] = new FileConstraint(mimeTypes: $processedMimeTypes, mimeTypesMessage: $mimeTypesMessage);
                $field->setCustomOption(FileField::OPTION_FILE_CONSTRAINTS, $constraints);
            }
        }

        $maxSize = $field->getCustomOption(FileField::OPTION_MAX_SIZE);
        if (null !== $maxSize) {
            $constraints = $field->getCustomOption(FileField::OPTION_FILE_CONSTRAINTS) ?? [];
            if (!\is_array($constraints)) {
                $constraints = [$constraints];
            }
            $maxSizeMessage = $field->getCustomOption(FileField::OPTION_MAX_SIZE_MESSAGE);
            $constraints[] = new FileConstraint(maxSize: $maxSize, maxSizeMessage: $maxSizeMessage);
            $field->setCustomOption(FileField::OPTION_FILE_CONSTRAINTS, $constraints);
        }

        $field->setFormTypeOption('file_constraints', $field->getCustomOption(FileField::OPTION_FILE_CONSTRAINTS));
        $field->setFormTypeOption('replaced_file_behavior', $field->getCustomOption(FileField::OPTION_REPLACED_FILE_BEHAVIOR));
        $field->setFormTypeOption('allow_delete', $field->getCustomOption(FileField::OPTION_DELETABLE));
        $field->setFormTypeOption('allow_view', $field->getCustomOption(FileField::OPTION_VIEWABLE));
        $field->setFormTypeOption('allow_download', $field->getCustomOption(FileField::OPTION_DOWNLOADABLE));
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
     * @param array<string|null>|null $files
     *
     * @return array<string|null>
     */
    private function getFilesPaths(?array $files, ?string $basePath): array
    {
        $filesPaths = [];
        foreach ($files as $file) {
            $filesPaths[] = $this->getFilePath($file, $basePath);
        }

        return $filesPaths;
    }

    private function getFilePath(?string $filePath, ?string $basePath): ?string
    {
        // add the base path only to files that are not absolute URLs (http or https) or protocol-relative URLs (//)
        if (null === $filePath || 0 !== preg_match('/^(http[s]?|\/\/)/i', $filePath)) {
            return $filePath;
        }

        // remove project path from filepath
        $filePath = str_replace($this->projectDir.\DIRECTORY_SEPARATOR.'public'.\DIRECTORY_SEPARATOR, '', $filePath);

        return isset($basePath)
            ? rtrim($basePath, '/').'/'.ltrim($filePath, '/')
            : '/'.ltrim($filePath, '/');
    }

    /**
     * @param array<string|null>|null $files
     *
     * @return array<string|null>
     */
    private function getFlysystemFilesPaths(?array $files, FilesystemOperator $filesystem, ?string $urlPrefix, string $propertyName): array
    {
        $filesPaths = [];
        foreach ($files as $file) {
            $filesPaths[] = $this->getFlysystemFilePath($file, $filesystem, $urlPrefix, $propertyName);
        }

        return $filesPaths;
    }

    private function getFlysystemFilePath(?string $filePath, FilesystemOperator $filesystem, ?string $urlPrefix, string $propertyName): ?string
    {
        if (null === $filePath) {
            return null;
        }

        // If it's already an absolute URL, return as-is
        if (0 !== preg_match('/^(http[s]?|\/\/)/i', $filePath)) {
            return $filePath;
        }

        if (null !== $urlPrefix) {
            return rtrim($urlPrefix, '/').'/'.ltrim($filePath, '/');
        }

        try {
            return $filesystem->publicUrl(ltrim($filePath, '/'));
        } catch (UnableToGeneratePublicUrl $e) {
            throw new \LogicException(sprintf('Unable to generate the public URL for the file stored in Flysystem for the "%s" field. Either configure the "public_url" option for this storage in your Flysystem configuration, or call setFlysystemUrlPrefix() on this field.', $propertyName), 0, $e);
        }
    }
}
