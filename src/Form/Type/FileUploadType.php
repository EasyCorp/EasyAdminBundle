<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Type;

use EasyCorp\Bundle\EasyAdminBundle\Config\Option\ReplacedFileBehavior;
use EasyCorp\Bundle\EasyAdminBundle\Form\DataTransformer\StringToFileTransformer;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\Model\FileUploadState;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\Model\FlysystemFile;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToGeneratePublicUrl;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\OptionsResolver\Exception\InvalidArgumentException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\All;

/**
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
class FileUploadType extends AbstractType implements DataMapperInterface
{
    public function __construct(
        private readonly string $projectDir,
        private readonly Filesystem $filesystem,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $uploadDir = $options['upload_dir'];
        $uploadFilename = $options['upload_filename'];
        $uploadValidate = $options['upload_validate'];
        $replacedFileBehavior = $options['replaced_file_behavior'];
        $allowAdd = $options['allow_add'];
        $flysystemStorage = $options['flysystem_storage'];

        if (ReplacedFileBehavior::KEEP === $replacedFileBehavior) {
            $uploadValidate = static fn (string $filename): string => $filename;
        } elseif (ReplacedFileBehavior::KEEP_OR_FAIL === $replacedFileBehavior) {
            if (null !== $flysystemStorage) {
                $uploadValidate = static function (string $filename) use ($flysystemStorage): string {
                    if ($flysystemStorage->fileExists($filename)) {
                        throw new TransformationFailedException(sprintf('The file "%s" already exists.', basename($filename)));
                    }

                    return $filename;
                };
            } else {
                $uploadValidate = static function (string $filename): string {
                    if (file_exists($filename)) {
                        throw new TransformationFailedException(sprintf('The file "%s" already exists.', basename($filename)));
                    }

                    return $filename;
                };
            }
        }

        $options['constraints'] = (bool) $options['multiple'] ? new All($options['file_constraints']) : $options['file_constraints'];
        unset($options['upload_dir'], $options['upload_new'], $options['upload_delete'], $options['upload_filename'], $options['upload_validate'], $options['download_path'], $options['allow_add'], $options['allow_delete'], $options['allow_view'], $options['allow_download'], $options['compound'], $options['file_constraints'], $options['replaced_file_behavior'], $options['flysystem_storage'], $options['flysystem_url_prefix']);

        $builder->add('file', FileType::class, $options);
        $builder->add('delete', CheckboxType::class, ['required' => false]);
        $builder->add('deleted_files', HiddenType::class, ['required' => false, 'mapped' => false]);

        $builder->setDataMapper($this);
        $builder->setAttribute('state', new FileUploadState($allowAdd));
        $builder->addModelTransformer(new StringToFileTransformer($uploadDir, $uploadFilename, $uploadValidate, $options['multiple'], $flysystemStorage));
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        /** @var FileUploadState $state */
        $state = $form->getConfig()->getAttribute('state');

        if ([] === $currentFiles = $state->getCurrentFiles()) {
            $data = $form->getNormData();

            if (null !== $data && [] !== $data) {
                $currentFiles = \is_array($data) ? $data : [$data];

                foreach ($currentFiles as $i => $file) {
                    if ($file instanceof UploadedFile) {
                        unset($currentFiles[$i]);
                    }
                }
            }
        }

        $uploadDir = $options['upload_dir'];
        $flysystemStorage = $options['flysystem_storage'];
        $flysystemUrlPrefix = $options['flysystem_url_prefix'];
        $currentFileNames = [];
        $currentFileUrls = [];
        foreach ($currentFiles as $file) {
            if ($file instanceof FlysystemFile) {
                $fileName = $file->getPathname();
                $currentFileNames[] = $fileName;
                $currentFileUrls[] = $this->resolveFlysystemFileUrl($flysystemStorage, $flysystemUrlPrefix, $fileName);
            } elseif ($file instanceof File) {
                $fileName = str_starts_with($file->getPathname(), $uploadDir)
                    ? mb_substr($file->getPathname(), mb_strlen($uploadDir))
                    : $file->getFilename();
                $currentFileNames[] = $fileName;
                $currentFileUrls[] = null;
            }
        }

        $view->vars['currentFiles'] = $currentFiles;
        $view->vars['currentFileNames'] = $currentFileNames;
        $view->vars['currentFileUrls'] = $currentFileUrls;
        $view->vars['multiple'] = $options['multiple'];
        $view->vars['allow_add'] = $options['allow_add'];
        $view->vars['allow_delete'] = $options['allow_delete'];
        $view->vars['allow_view'] = $options['allow_view'];
        $view->vars['allow_download'] = $options['allow_download'];
        $view->vars['download_path'] = $options['download_path'];
    }

    private function resolveFlysystemFileUrl(?FilesystemOperator $filesystem, ?string $urlPrefix, string $fileName): ?string
    {
        if (null !== $urlPrefix) {
            return rtrim($urlPrefix, '/').'/'.ltrim($fileName, '/');
        }

        if (null === $filesystem) {
            return null;
        }

        try {
            return $filesystem->publicUrl(ltrim($fileName, '/'));
        } catch (UnableToGeneratePublicUrl) {
            return null;
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $uploadNew = static function (UploadedFile $file, string $uploadDir, string $fileName) {
            $subDir = \dirname($fileName);
            if ('.' !== $subDir) {
                $uploadDir = rtrim($uploadDir, \DIRECTORY_SEPARATOR).\DIRECTORY_SEPARATOR.$subDir;
                $fileName = basename($fileName);
            }
            $file->move($uploadDir, $fileName);
        };

        $uploadDelete = static function (File $file) {
            unlink($file->getPathname());
        };

        // the return value MUST be a safe relative path:
        //   * no ".." segments
        //   * no leading "/" or "\"
        //   * no Windows drive letters
        //   * no null bytes
        //
        // values that violate this contract are rejected on read (see
        // StringToFileTransformer::doTransform) and the form behaves as if
        // no file were stored. Overrides must preserve this contract.
        $uploadFilename = static fn (UploadedFile $file): string => basename(str_replace('\\', '/', $file->getClientOriginalName()));

        // the upload_validate callable receives the full path (upload_dir + filename)
        // so it can call file_exists() to detect collisions. Its return value may be
        // either a full path inside upload_dir (the leading upload_dir will be stripped
        // by StringToFileTransformer) or a plain relative filename. In both cases the
        // value that is finally stored must satisfy the same safe-path contract as
        // upload_filename (see comment above).
        $uploadValidate = static function (string $filename): string {
            if (!file_exists($filename)) {
                return $filename;
            }

            $index = 1;
            $pathInfo = pathinfo($filename);
            while (file_exists($filename = sprintf('%s/%s_%d.%s', $pathInfo['dirname'], $pathInfo['filename'], $index, $pathInfo['extension']))) {
                ++$index;
            }

            return $filename;
        };

        $downloadPath = fn (Options $options) => mb_substr($options['upload_dir'], mb_strlen($this->projectDir.'/public/'));

        $allowAdd = static fn (Options $options) => $options['multiple'];

        $dataClass = static fn (Options $options) => $options['multiple'] ? null : File::class;

        $emptyData = static fn (Options $options) => $options['multiple'] ? [] : null;

        $resolver->setDefaults([
            'upload_dir' => $this->projectDir.'/public/uploads/files/',
            'upload_new' => $uploadNew,
            'upload_delete' => $uploadDelete,
            'upload_filename' => $uploadFilename,
            'upload_validate' => $uploadValidate,
            'download_path' => $downloadPath,
            'allow_add' => $allowAdd,
            'allow_delete' => true,
            'allow_view' => true,
            'allow_download' => true,
            'replaced_file_behavior' => ReplacedFileBehavior::DELETE,
            'data_class' => $dataClass,
            'empty_data' => $emptyData,
            'multiple' => false,
            'required' => false,
            'error_bubbling' => false,
            'allow_file_upload' => true,
            'file_constraints' => [],
            'flysystem_storage' => null,
            'flysystem_url_prefix' => null,
        ]);

        $resolver->setAllowedTypes('upload_dir', 'string');
        $resolver->setAllowedTypes('upload_new', 'callable');
        $resolver->setAllowedTypes('upload_delete', 'callable');
        $resolver->setAllowedTypes('upload_filename', ['string', 'callable']);
        $resolver->setAllowedTypes('upload_validate', 'callable');
        $resolver->setAllowedTypes('download_path', ['null', 'string']);
        $resolver->setAllowedTypes('allow_add', 'bool');
        $resolver->setAllowedTypes('allow_delete', 'bool');
        $resolver->setAllowedTypes('allow_view', 'bool');
        $resolver->setAllowedTypes('allow_download', 'bool');
        $resolver->setAllowedValues('replaced_file_behavior', [ReplacedFileBehavior::DELETE, ReplacedFileBehavior::KEEP, ReplacedFileBehavior::KEEP_OR_FAIL]);
        $resolver->setAllowedTypes('file_constraints', [Constraint::class, Constraint::class.'[]']);
        $resolver->setAllowedTypes('flysystem_storage', ['null', FilesystemOperator::class]);
        $resolver->setAllowedTypes('flysystem_url_prefix', ['null', 'string']);

        $resolver->setNormalizer('upload_dir', function (Options $options, string $value): string {
            if (null !== $options['flysystem_storage']) {
                // For Flysystem, just ensure trailing separator and skip local filesystem checks
                if (\DIRECTORY_SEPARATOR !== mb_substr($value, -1) && '/' !== mb_substr($value, -1)) {
                    $value .= '/';
                }

                return $value;
            }

            if (\DIRECTORY_SEPARATOR !== mb_substr($value, -1)) {
                $value .= \DIRECTORY_SEPARATOR;
            }

            $isLocalFilesystem = false === filter_var($value, \FILTER_VALIDATE_URL);

            if ($isLocalFilesystem && !str_starts_with($value, $this->projectDir)) {
                $value = $this->projectDir.'/'.$value;
            }

            if ($isLocalFilesystem && !is_dir($value)) {
                $this->filesystem->mkdir($value);
            }

            if ($isLocalFilesystem && !is_writable($value)) {
                throw new InvalidArgumentException(sprintf('The upload directory "%s" is not writable.', $value));
            }

            return $value;
        });
        $resolver->setNormalizer('upload_filename', static function (Options $options, $fileNamePatternOrCallable) {
            $resolvePatternPlaceholders = static function (string $filename, UploadedFile $file): string {
                $uuid = Uuid::v4();

                return strtr($filename, [
                    '[contenthash]' => sha1_file($file->getRealPath()),
                    '[DD]' => date('d'),
                    '[day]' => date('d'),
                    '[extension]' => $file->guessExtension(),
                    '[hh]' => date('H'),
                    '[mm]' => date('i'),
                    '[MM]' => date('m'),
                    '[month]' => date('m'),
                    '[name]' => pathinfo($file->getClientOriginalName(), \PATHINFO_FILENAME),
                    '[randomhash]' => bin2hex(random_bytes(20)),
                    '[slug]' => (new AsciiSlugger())
                        ->slug(pathinfo($file->getClientOriginalName(), \PATHINFO_FILENAME))
                        ->lower()
                        ->toString(),
                    '[ss]' => date('s'),
                    '[timestamp]' => time(),
                    '[uuid]' => $uuid->toRfc4122(),
                    '[uuid32]' => $uuid->toBase32(),
                    '[uuid58]' => $uuid->toBase58(),
                    '[ulid]' => new Ulid(),
                    '[YY]' => date('y'),
                    '[YYYY]' => date('Y'),
                    '[year]' => date('Y'),
                ]);
            };

            if (\is_callable($fileNamePatternOrCallable)) {
                return static function (UploadedFile $file) use ($fileNamePatternOrCallable, $resolvePatternPlaceholders) {
                    return $resolvePatternPlaceholders($fileNamePatternOrCallable($file), $file);
                };
            }

            $deprecatedPlaceholders = ['[day]' => '[DD]', '[month]' => '[MM]', '[year]' => '[YYYY]'];
            foreach ($deprecatedPlaceholders as $old => $new) {
                if (str_contains($fileNamePatternOrCallable, $old)) {
                    @trigger_deprecation('easycorp/easyadmin-bundle', '5.1.0',
                        'The "%s" placeholder in file upload name patterns is deprecated, use "%s" instead. It will be removed in EasyAdmin 6.0.', $old, $new);
                }
            }

            return static function (UploadedFile $file) use ($fileNamePatternOrCallable, $resolvePatternPlaceholders) {
                return $resolvePatternPlaceholders($fileNamePatternOrCallable, $file);
            };
        });
        $resolver->setNormalizer('allow_add', static function (Options $options, string $value): bool {
            if ((bool) $value && !$options['multiple']) {
                throw new InvalidArgumentException('Setting "allow_add" option to "true" when "multiple" option is "false" is not supported.');
            }

            return (bool) $value;
        });
        $resolver->setNormalizer('file_constraints', static function (Options $options, $constraints) {
            return \is_object($constraints) ? [$constraints] : (array) $constraints;
        });
    }

    public function getBlockPrefix(): string
    {
        return 'ea_fileupload';
    }

    public function mapDataToForms(mixed $currentFiles, \Traversable $forms): void
    {
        /** @var FormInterface $fileForm */
        $fileForm = current(iterator_to_array($forms));
        $fileForm->setData($currentFiles);
    }

    public function mapFormsToData($forms, &$currentFiles): void
    {
        /** @var FormInterface[] $children */
        $children = iterator_to_array($forms);
        $uploadedFiles = $children['file']->getData();

        /** @var FileUploadState $state */
        $state = $children['file']->getParent()->getConfig()->getAttribute('state');
        $state->setCurrentFiles($currentFiles);
        $state->setUploadedFiles($uploadedFiles);
        $state->setDelete($children['delete']->getData());

        $deletedFilesJson = $children['deleted_files']->getData();
        $deletedFileNames = \is_string($deletedFilesJson) ? json_decode($deletedFilesJson, true) : [];
        $state->setDeletedFiles(\is_array($deletedFileNames) ? $deletedFileNames : []);

        if (!$state->isModified()) {
            return;
        }

        if ($state->isDelete()) {
            $currentFiles = $uploadedFiles;
        } elseif ([] !== $state->getDeletedFiles()) {
            if (\is_array($currentFiles)) {
                $currentFilesArray = $currentFiles;
            } elseif (null !== $currentFiles && false !== $currentFiles) {
                $currentFilesArray = [$currentFiles];
            } else {
                $currentFilesArray = [];
            }
            $remainingFiles = array_values(array_filter($currentFilesArray, static function ($file) use ($state) {
                $fileName = $file instanceof FlysystemFile ? $file->getPathname() : $file->getFilename();

                return !\in_array($fileName, $state->getDeletedFiles(), true);
            }));
            if ($state->isAddAllowed()) {
                $currentFiles = array_merge($remainingFiles, $uploadedFiles);
            } elseif ([] !== $uploadedFiles) {
                $currentFiles = $uploadedFiles;
            } else {
                // in single-file mode, normalize empty result to null
                // (consistent with the delete-all checkbox behavior)
                $currentFiles = [] === $remainingFiles ? null : $remainingFiles;
            }
        } elseif ($state->isAddAllowed()) {
            $currentFiles = array_merge($currentFiles, $uploadedFiles);
        } else {
            $currentFiles = $uploadedFiles;
        }
    }
}
