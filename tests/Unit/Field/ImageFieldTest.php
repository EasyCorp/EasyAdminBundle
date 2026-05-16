<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Field;

use EasyCorp\Bundle\EasyAdminBundle\Config\Option\ReplacedFileBehavior;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\FileUploadType;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\NotNull;

class ImageFieldTest extends AbstractFieldTest
{
    protected function setUp(): void
    {
        parent::setUp();

        // imageField configurator requires Symfony services for file handling
        // for these tests, we'll use a no-op configurator to test the field options
        $this->configurator = new class implements FieldConfiguratorInterface {
            public function supports(FieldDto $field, EntityDto $entityDto): bool
            {
                return ImageField::class === $field->getFieldFqcn();
            }

            public function configure(FieldDto $field, EntityDto $entityDto, AdminContext $context): void
            {
                // no-op for basic option testing
            }
        };
    }

    public function testDefaultOptions(): void
    {
        $field = ImageField::new('image');
        $fieldDto = $this->configure($field);

        self::assertNull($fieldDto->getCustomOption(ImageField::OPTION_BASE_PATH));
        self::assertNull($fieldDto->getCustomOption(ImageField::OPTION_UPLOAD_DIR));
        self::assertSame('[name].[extension]', $fieldDto->getCustomOption(ImageField::OPTION_UPLOADED_FILE_NAME_PATTERN));
        self::assertSame(FileUploadType::class, $fieldDto->getFormType());
        self::assertStringContainsString('field-image', $fieldDto->getCssClass());
    }

    public function testDefaultFileConstraints(): void
    {
        $field = ImageField::new('image');
        $fieldDto = $this->configure($field);

        $constraints = $fieldDto->getCustomOption(ImageField::OPTION_FILE_CONSTRAINTS);
        self::assertIsArray($constraints);
        self::assertCount(1, $constraints);
        self::assertInstanceOf(Image::class, $constraints[0]);
    }

    public function testFieldWithNullValue(): void
    {
        $field = ImageField::new('image');
        $field->setValue(null);
        $fieldDto = $this->configure($field);

        self::assertNull($fieldDto->getValue());
    }

    public function testFieldWithFilename(): void
    {
        $field = ImageField::new('image');
        $field->setValue('profile.jpg');
        $fieldDto = $this->configure($field);

        self::assertSame('profile.jpg', $fieldDto->getValue());
    }

    public function testSetBasePath(): void
    {
        $field = ImageField::new('image');
        $field->setBasePath('/uploads/images/');
        $fieldDto = $this->configure($field);

        self::assertSame('/uploads/images/', $fieldDto->getCustomOption(ImageField::OPTION_BASE_PATH));
    }

    public function testSetUploadDir(): void
    {
        $field = ImageField::new('image');
        $field->setUploadDir('public/uploads/images/');
        $fieldDto = $this->configure($field);

        self::assertSame('public/uploads/images/', $fieldDto->getCustomOption(ImageField::OPTION_UPLOAD_DIR));
    }

    public function testSetUploadedFileNamePatternWithString(): void
    {
        $field = ImageField::new('image');
        $field->setUploadedFileNamePattern('[year]/[month]/[slug].[extension]');
        $fieldDto = $this->configure($field);

        self::assertSame('[year]/[month]/[slug].[extension]', $fieldDto->getCustomOption(ImageField::OPTION_UPLOADED_FILE_NAME_PATTERN));
    }

    public function testSetUploadedFileNamePatternWithClosure(): void
    {
        $pattern = static fn ($file) => 'custom_'.$file->getFilename();
        $field = ImageField::new('image');
        $field->setUploadedFileNamePattern($pattern);
        $fieldDto = $this->configure($field);

        self::assertSame($pattern, $fieldDto->getCustomOption(ImageField::OPTION_UPLOADED_FILE_NAME_PATTERN));
    }

    public function testSetFileConstraintsWithSingleConstraint(): void
    {
        $supportsNamedMaxSize = false;

        $constructor = (new \ReflectionClass(Image::class))->getConstructor();
        if ($constructor instanceof \ReflectionMethod) {
            foreach ($constructor->getParameters() as $parameter) {
                if ('maxSize' === $parameter->getName()) {
                    $supportsNamedMaxSize = true;
                    break;
                }
            }
        }

        if ($supportsNamedMaxSize) {
            $constraint = new Image(maxSize: '5M');
        } else {
            $constraint = new Image(['maxSize' => '5M']);
        }

        $field = ImageField::new('image');
        $field->setFileConstraints($constraint);
        $fieldDto = $this->configure($field);

        self::assertSame([$constraint], $fieldDto->getCustomOption(ImageField::OPTION_FILE_CONSTRAINTS));
    }

    public function testSetFileConstraintsWithMultipleConstraints(): void
    {
        $supportsNamedMaxSize = false;

        $constructor = (new \ReflectionClass(Image::class))->getConstructor();
        if ($constructor instanceof \ReflectionMethod) {
            foreach ($constructor->getParameters() as $parameter) {
                if ('maxSize' === $parameter->getName()) {
                    $supportsNamedMaxSize = true;
                    break;
                }
            }
        }

        if ($supportsNamedMaxSize) {
            $imageConstraint = new Image(maxSize: '5M');
        } else {
            $imageConstraint = new Image(['maxSize' => '5M']);
        }

        $constraints = [
            $imageConstraint,
            new NotNull(),
        ];
        $field = ImageField::new('image');
        $field->setFileConstraints($constraints);
        $fieldDto = $this->configure($field);

        self::assertSame($constraints, $fieldDto->getCustomOption(ImageField::OPTION_FILE_CONSTRAINTS));
    }

    public function testSetFileConstraintsRejectsNonConstraintArrayItems(): void
    {
        $field = ImageField::new('image');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('expects a "Symfony\Component\Validator\Constraint" instance or an array of them');

        $field->setFileConstraints([new Image(), 'not-a-constraint']);
    }

    public function testUploadPatternPlaceholders(): void
    {
        // test various placeholders that can be used
        $patterns = [
            '[DD]',
            '[MM]',
            '[YYYY]',
            '[YY]',
            '[hh]',
            '[mm]',
            '[ss]',
            '[day]',
            '[month]',
            '[year]',
            '[timestamp]',
            '[name]',
            '[slug]',
            '[extension]',
            '[contenthash]',
            '[randomhash]',
            '[uuid]',
            '[uuid32]',
            '[uuid58]',
            '[ulid]',
        ];

        foreach ($patterns as $pattern) {
            $field = ImageField::new('image');
            $field->setUploadedFileNamePattern($pattern);
            $fieldDto = $this->configure($field);

            self::assertSame($pattern, $fieldDto->getCustomOption(ImageField::OPTION_UPLOADED_FILE_NAME_PATTERN));
        }
    }

    public function testComplexUploadPattern(): void
    {
        $pattern = '[YYYY]/[MM]/[DD]/[slug]-[contenthash].[extension]';
        $field = ImageField::new('image');
        $field->setUploadedFileNamePattern($pattern);
        $fieldDto = $this->configure($field);

        self::assertSame($pattern, $fieldDto->getCustomOption(ImageField::OPTION_UPLOADED_FILE_NAME_PATTERN));
    }

    public function testDefaultMimeTypes(): void
    {
        $field = ImageField::new('image');
        $fieldDto = $this->configure($field);

        self::assertSame('image/*', $fieldDto->getCustomOption(ImageField::OPTION_MIME_TYPES));
    }

    public function testMimeTypesWithErrorMessage(): void
    {
        $field = ImageField::new('image');
        $field->mimeTypes('image/png,image/jpeg', 'Only PNG and JPEG images are allowed (got {{ type }})');
        $fieldDto = $this->configure($field);

        self::assertSame('image/png,image/jpeg', $fieldDto->getCustomOption(ImageField::OPTION_MIME_TYPES));
        self::assertSame('Only PNG and JPEG images are allowed (got {{ type }})', $fieldDto->getCustomOption(ImageField::OPTION_MIME_TYPES_MESSAGE));
    }

    public function testDefaultMimeTypesMessageIsNull(): void
    {
        $field = ImageField::new('image');
        $fieldDto = $this->configure($field);

        self::assertNull($fieldDto->getCustomOption(ImageField::OPTION_MIME_TYPES_MESSAGE));
    }

    public function testSetMaxSize(): void
    {
        $field = ImageField::new('image');
        $field->maxSize('5M');
        $fieldDto = $this->configure($field);

        self::assertSame('5M', $fieldDto->getCustomOption(ImageField::OPTION_MAX_SIZE));
        self::assertNull($fieldDto->getCustomOption(ImageField::OPTION_MAX_SIZE_MESSAGE));
    }

    public function testSetMaxSizeWithInteger(): void
    {
        $field = ImageField::new('image');
        $field->maxSize(2097152);
        $fieldDto = $this->configure($field);

        self::assertSame(2097152, $fieldDto->getCustomOption(ImageField::OPTION_MAX_SIZE));
    }

    public function testSetMaxSizeWithErrorMessage(): void
    {
        $field = ImageField::new('image');
        $field->maxSize('2M', 'Image {{ name }} is too large ({{ size }} {{ suffix }})');
        $fieldDto = $this->configure($field);

        self::assertSame('2M', $fieldDto->getCustomOption(ImageField::OPTION_MAX_SIZE));
        self::assertSame('Image {{ name }} is too large ({{ size }} {{ suffix }})', $fieldDto->getCustomOption(ImageField::OPTION_MAX_SIZE_MESSAGE));
    }

    public function testDefaultMaxSizeIsNull(): void
    {
        $field = ImageField::new('image');
        $fieldDto = $this->configure($field);

        self::assertNull($fieldDto->getCustomOption(ImageField::OPTION_MAX_SIZE));
        self::assertNull($fieldDto->getCustomOption(ImageField::OPTION_MAX_SIZE_MESSAGE));
    }

    public function testDefaultViewable(): void
    {
        $field = ImageField::new('image');
        $fieldDto = $this->configure($field);

        self::assertTrue($fieldDto->getCustomOption(ImageField::OPTION_VIEWABLE));
    }

    public function testIsViewableFalse(): void
    {
        $field = ImageField::new('image');
        $field->isViewable(false);
        $fieldDto = $this->configure($field);

        self::assertFalse($fieldDto->getCustomOption(ImageField::OPTION_VIEWABLE));
    }

    public function testDefaultDownloadable(): void
    {
        $field = ImageField::new('image');
        $fieldDto = $this->configure($field);

        self::assertTrue($fieldDto->getCustomOption(ImageField::OPTION_DOWNLOADABLE));
    }

    public function testIsDownloadableFalse(): void
    {
        $field = ImageField::new('image');
        $field->isDownloadable(false);
        $fieldDto = $this->configure($field);

        self::assertFalse($fieldDto->getCustomOption(ImageField::OPTION_DOWNLOADABLE));
    }

    public function testDefaultReplacedFileBehavior(): void
    {
        $field = ImageField::new('image');
        $fieldDto = $this->configure($field);

        self::assertSame(ReplacedFileBehavior::DELETE, $fieldDto->getCustomOption(ImageField::OPTION_REPLACED_FILE_BEHAVIOR));
    }

    public function testDeleteReplacedFile(): void
    {
        $field = ImageField::new('image');
        $field->deleteReplacedFile();
        $fieldDto = $this->configure($field);

        self::assertSame(ReplacedFileBehavior::DELETE, $fieldDto->getCustomOption(ImageField::OPTION_REPLACED_FILE_BEHAVIOR));
    }

    public function testKeepReplacedFile(): void
    {
        $field = ImageField::new('image');
        $field->keepReplacedFile();
        $fieldDto = $this->configure($field);

        self::assertSame(ReplacedFileBehavior::KEEP, $fieldDto->getCustomOption(ImageField::OPTION_REPLACED_FILE_BEHAVIOR));
    }

    public function testKeepReplacedFileOrFail(): void
    {
        $field = ImageField::new('image');
        $field->keepReplacedFileOrFail();
        $fieldDto = $this->configure($field);

        self::assertSame(ReplacedFileBehavior::KEEP_OR_FAIL, $fieldDto->getCustomOption(ImageField::OPTION_REPLACED_FILE_BEHAVIOR));
    }

    public function testDefaultDeletable(): void
    {
        $field = ImageField::new('image');
        $fieldDto = $this->configure($field);

        self::assertTrue($fieldDto->getCustomOption(ImageField::OPTION_DELETABLE));
    }

    public function testIsDeletableFalse(): void
    {
        $field = ImageField::new('image');
        $field->isDeletable(false);
        $fieldDto = $this->configure($field);

        self::assertFalse($fieldDto->getCustomOption(ImageField::OPTION_DELETABLE));
    }

    public function testIsDeletableTrue(): void
    {
        $field = ImageField::new('image');
        $field->isDeletable(false);
        $field->isDeletable(true);
        $fieldDto = $this->configure($field);

        self::assertTrue($fieldDto->getCustomOption(ImageField::OPTION_DELETABLE));
    }
}
