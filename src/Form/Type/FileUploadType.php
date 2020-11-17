<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Type;

use EasyCorp\Bundle\EasyAdminBundle\Form\DataTransformer\StringToFileTransformer;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\Model\FileUploadState;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\OptionsResolver\Exception\InvalidArgumentException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\Uid\Uuid;

/**
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
class FileUploadType extends AbstractType implements DataMapperInterface
{
    private $projectDir;

    public function __construct(string $projectDir)
    {
        $this->projectDir = $projectDir;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $uploadDir = $options['upload_dir'];
        $uploadFilename = $options['upload_filename'];
        $uploadValidate = $options['upload_validate'];
        $allowAdd = $options['allow_add'];
        unset($options['upload_dir'], $options['upload_new'], $options['upload_delete'], $options['upload_filename'], $options['upload_validate'], $options['download_path'], $options['allow_add'], $options['allow_delete'], $options['compound']);

        $builder->add('file', FileType::class, $options);
        $builder->add('delete', CheckboxType::class, ['required' => false]);

        $builder->setDataMapper($this);
        $builder->setAttribute('state', new FileUploadState($allowAdd));
        $builder->addModelTransformer(new StringToFileTransformer($uploadDir, $uploadFilename, $uploadValidate, $options['multiple']));
    }

    /**
     * {@inheritdoc}
     */
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

        $view->vars['currentFiles'] = $currentFiles;
        $view->vars['multiple'] = $options['multiple'];
        $view->vars['allow_add'] = $options['allow_add'];
        $view->vars['allow_delete'] = $options['allow_delete'];
        $view->vars['download_path'] = $options['download_path'];
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $uploadNew = static function (UploadedFile $file, string $uploadDir, string $fileName) {
            $name = str_replace('\\', '/', $fileName);
            $pos = strrpos($name, '/');
            $subDir = false === $pos ? '' : substr($name, 0, $pos);
            $name = false === $pos ? $name : substr($name, $pos + 1);

            $file->move(rtrim($uploadDir, '/\\').\DIRECTORY_SEPARATOR.$subDir, $name);
        };

        $uploadDelete = static function (File $file) {
            unlink($file->getPathname());
        };

        $uploadFilename = static function (UploadedFile $file): string {
            return $file->getClientOriginalName();
        };

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

        $downloadPath = function (Options $options) {
            return mb_substr($options['upload_dir'], mb_strlen($this->projectDir.'/public/'));
        };

        $allowAdd = static function (Options $options) {
            return $options['multiple'];
        };

        $dataClass = static function (Options $options) {
            return $options['multiple'] ? null : File::class;
        };

        $emptyData = static function (Options $options) {
            return $options['multiple'] ? [] : null;
        };

        $resolver->setDefaults([
            'upload_dir' => $this->projectDir.'/public/uploads/files/',
            'upload_new' => $uploadNew,
            'upload_delete' => $uploadDelete,
            'upload_filename' => $uploadFilename,
            'upload_validate' => $uploadValidate,
            'download_path' => $downloadPath,
            'allow_add' => $allowAdd,
            'allow_delete' => true,
            'data_class' => $dataClass,
            'empty_data' => $emptyData,
            'multiple' => false,
            'required' => false,
            'error_bubbling' => false,
            'allow_file_upload' => true,
        ]);

        $resolver->setAllowedTypes('upload_dir', 'string');
        $resolver->setAllowedTypes('upload_new', 'callable');
        $resolver->setAllowedTypes('upload_delete', 'callable');
        $resolver->setAllowedTypes('upload_filename', ['string', 'callable']);
        $resolver->setAllowedTypes('upload_validate', 'callable');
        $resolver->setAllowedTypes('download_path', ['null', 'string']);
        $resolver->setAllowedTypes('allow_add', 'bool');
        $resolver->setAllowedTypes('allow_delete', 'bool');

        $resolver->setNormalizer('upload_dir', function (Options $options, string $value): string {
            if (\DIRECTORY_SEPARATOR !== mb_substr($value, -1)) {
                $value .= \DIRECTORY_SEPARATOR;
            }

            if (0 !== mb_strpos($value, \DIRECTORY_SEPARATOR)) {
                $value = $this->projectDir.'/'.$value;
            }

            if ('' !== $value && (!is_dir($value) || !is_writable($value))) {
                throw new InvalidArgumentException(sprintf('Invalid upload directory "%s" it does not exist or is not writable.', $value));
            }

            return $value;
        });
        $resolver->setNormalizer('upload_filename', static function (Options $options, $value) {
            if (\is_callable($value)) {
                return $value;
            }

            return static function (UploadedFile $file) use ($value) {
                return strtr($value, [
                    '[contenthash]' => sha1_file($file->getRealPath()),
                    '[day]' => date('d'),
                    '[extension]' => $file->guessClientExtension(),
                    '[month]' => date('m'),
                    '[name]' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                    '[randomhash]' => bin2hex(random_bytes(20)),
                    '[slug]' => (new AsciiSlugger())
                        ->slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
                        ->lower()
                        ->toString(),
                    '[timestamp]' => time(),
                    '[uuid]' => Uuid::v4()->toRfc4122(),
                    '[year]' => date('Y'),
                ]);
            };
        });
        $resolver->setNormalizer('allow_add', static function (Options $options, string $value): bool {
            if ($value && !$options['multiple']) {
                throw new InvalidArgumentException('Setting "allow_add" option to "true" when "multiple" option is "false" is not supported.');
            }

            return $value;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return 'ea_fileupload';
    }

    /**
     * {@inheritdoc}
     */
    public function mapDataToForms($currentFiles, $forms): void
    {
        /** @var FormInterface $fileForm */
        $fileForm = current(iterator_to_array($forms));
        $fileForm->setData($currentFiles);
    }

    /**
     * {@inheritdoc}
     */
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

        if (!$state->isModified()) {
            return;
        }

        if ($state->isAddAllowed() && !$state->isDelete()) {
            $currentFiles = array_merge($currentFiles, $uploadedFiles);
        } else {
            $currentFiles = $uploadedFiles;
        }
    }
}
