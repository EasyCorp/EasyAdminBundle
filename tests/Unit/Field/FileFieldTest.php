<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Field;

use EasyCorp\Bundle\EasyAdminBundle\Config\Option\ReplacedFileBehavior;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\FileField;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\FileUploadType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotNull;

class FileFieldTest extends AbstractFieldTest
{
    protected function setUp(): void
    {
        parent::setUp();

        // FileField configurator requires Symfony services for file handling
        // for these tests, we'll use a no-op configurator to test the field options
        $this->configurator = new class implements FieldConfiguratorInterface {
            public function supports(FieldDto $field, EntityDto $entityDto): bool
            {
                return FileField::class === $field->getFieldFqcn();
            }

            public function configure(FieldDto $field, EntityDto $entityDto, AdminContext $context): void
            {
                // no-op for basic option testing
            }
        };
    }

    public function testDefaultOptions(): void
    {
        $field = FileField::new('document');
        $fieldDto = $this->configure($field);

        self::assertNull($fieldDto->getCustomOption(FileField::OPTION_BASE_PATH));
        self::assertNull($fieldDto->getCustomOption(FileField::OPTION_UPLOAD_DIR));
        self::assertSame('[name].[extension]', $fieldDto->getCustomOption(FileField::OPTION_UPLOADED_FILE_NAME_PATTERN));
        self::assertSame(FileUploadType::class, $fieldDto->getFormType());
        self::assertStringContainsString('field-file', $fieldDto->getCssClass());
    }

    public function testDefaultFileConstraints(): void
    {
        $field = FileField::new('document');
        $fieldDto = $this->configure($field);

        $constraints = $fieldDto->getCustomOption(FileField::OPTION_FILE_CONSTRAINTS);
        self::assertIsArray($constraints);
        self::assertCount(0, $constraints);
    }

    public function testFieldWithNullValue(): void
    {
        $field = FileField::new('document');
        $field->setValue(null);
        $fieldDto = $this->configure($field);

        self::assertNull($fieldDto->getValue());
    }

    public function testFieldWithFilename(): void
    {
        $field = FileField::new('document');
        $field->setValue('report.pdf');
        $fieldDto = $this->configure($field);

        self::assertSame('report.pdf', $fieldDto->getValue());
    }

    public function testSetBasePath(): void
    {
        $field = FileField::new('document');
        $field->setBasePath('/uploads/files/');
        $fieldDto = $this->configure($field);

        self::assertSame('/uploads/files/', $fieldDto->getCustomOption(FileField::OPTION_BASE_PATH));
    }

    public function testSetUploadDir(): void
    {
        $field = FileField::new('document');
        $field->setUploadDir('public/uploads/files/');
        $fieldDto = $this->configure($field);

        self::assertSame('public/uploads/files/', $fieldDto->getCustomOption(FileField::OPTION_UPLOAD_DIR));
    }

    public function testSetUploadedFileNamePatternWithString(): void
    {
        $field = FileField::new('document');
        $field->setUploadedFileNamePattern('[year]/[month]/[slug].[extension]');
        $fieldDto = $this->configure($field);

        self::assertSame('[year]/[month]/[slug].[extension]', $fieldDto->getCustomOption(FileField::OPTION_UPLOADED_FILE_NAME_PATTERN));
    }

    public function testSetUploadedFileNamePatternWithClosure(): void
    {
        $pattern = static fn ($file) => 'custom_'.$file->getFilename();
        $field = FileField::new('document');
        $field->setUploadedFileNamePattern($pattern);
        $fieldDto = $this->configure($field);

        self::assertSame($pattern, $fieldDto->getCustomOption(FileField::OPTION_UPLOADED_FILE_NAME_PATTERN));
    }

    public function testSetFileConstraintsWithSingleConstraint(): void
    {
        $constraint = new File(maxSize: '10M');

        $field = FileField::new('document');
        $field->setFileConstraints($constraint);
        $fieldDto = $this->configure($field);

        self::assertSame([$constraint], $fieldDto->getCustomOption(FileField::OPTION_FILE_CONSTRAINTS));
    }

    public function testSetFileConstraintsWithMultipleConstraints(): void
    {
        $constraints = [
            new File(maxSize: '10M'),
            new NotNull(),
        ];
        $field = FileField::new('document');
        $field->setFileConstraints($constraints);
        $fieldDto = $this->configure($field);

        self::assertSame($constraints, $fieldDto->getCustomOption(FileField::OPTION_FILE_CONSTRAINTS));
    }

    public function testSetFileConstraintsRejectsNonConstraintArrayItems(): void
    {
        $field = FileField::new('document');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('expects a "Symfony\Component\Validator\Constraint" instance or an array of them');

        $field->setFileConstraints([new File(maxSize: '10M'), 'not-a-constraint']);
    }

    public function testUploadPatternPlaceholders(): void
    {
        $patterns = [
            '[DD]',
            '[MM]',
            '[YYYY]',
            '[YY]',
            '[hh]',
            '[mm]',
            '[ss]',
            '[timestamp]',
            '[name]',
            '[slug]',
            '[extension]',
            '[contenthash]',
            '[randomhash]',
            '[uuid]',
            '[ulid]',
        ];

        foreach ($patterns as $pattern) {
            $field = FileField::new('document');
            $field->setUploadedFileNamePattern($pattern);
            $fieldDto = $this->configure($field);

            self::assertSame($pattern, $fieldDto->getCustomOption(FileField::OPTION_UPLOADED_FILE_NAME_PATTERN));
        }
    }

    public function testComplexUploadPattern(): void
    {
        $pattern = '[YYYY]/[MM]/[DD]/[slug]-[contenthash].[extension]';
        $field = FileField::new('document');
        $field->setUploadedFileNamePattern($pattern);
        $fieldDto = $this->configure($field);

        self::assertSame($pattern, $fieldDto->getCustomOption(FileField::OPTION_UPLOADED_FILE_NAME_PATTERN));
    }

    public function testSetFileAccept(): void
    {
        $field = FileField::new('document');
        $field->mimeTypes('.pdf,.docx,.xlsx');
        $fieldDto = $this->configure($field);

        self::assertSame('.pdf,.docx,.xlsx', $fieldDto->getCustomOption(FileField::OPTION_MIME_TYPES));
    }

    public function testDefaultAcceptIsNull(): void
    {
        $field = FileField::new('document');
        $fieldDto = $this->configure($field);

        self::assertNull($fieldDto->getCustomOption(FileField::OPTION_MIME_TYPES));
    }

    public function testSetFileAcceptWithMimeTypes(): void
    {
        $field = FileField::new('document');
        $field->mimeTypes('image/*,application/pdf');
        $fieldDto = $this->configure($field);

        self::assertSame('image/*,application/pdf', $fieldDto->getCustomOption(FileField::OPTION_MIME_TYPES));
    }

    public function testSetFileAcceptWithMixedTokens(): void
    {
        $field = FileField::new('document');
        $field->mimeTypes('.pdf, image/*, .docx');
        $fieldDto = $this->configure($field);

        self::assertSame('.pdf, image/*, .docx', $fieldDto->getCustomOption(FileField::OPTION_MIME_TYPES));
    }

    public function testMimeTypesWithErrorMessage(): void
    {
        $field = FileField::new('document');
        $field->mimeTypes('.pdf,.docx', 'Only PDF and Word files are allowed (got {{ type }})');
        $fieldDto = $this->configure($field);

        self::assertSame('.pdf,.docx', $fieldDto->getCustomOption(FileField::OPTION_MIME_TYPES));
        self::assertSame('Only PDF and Word files are allowed (got {{ type }})', $fieldDto->getCustomOption(FileField::OPTION_MIME_TYPES_MESSAGE));
    }

    public function testDefaultMimeTypesMessageIsNull(): void
    {
        $field = FileField::new('document');
        $fieldDto = $this->configure($field);

        self::assertNull($fieldDto->getCustomOption(FileField::OPTION_MIME_TYPES_MESSAGE));
    }

    public function testSetMaxSize(): void
    {
        $field = FileField::new('document');
        $field->maxSize('10M');
        $fieldDto = $this->configure($field);

        self::assertSame('10M', $fieldDto->getCustomOption(FileField::OPTION_MAX_SIZE));
        self::assertNull($fieldDto->getCustomOption(FileField::OPTION_MAX_SIZE_MESSAGE));
    }

    public function testSetMaxSizeWithInteger(): void
    {
        $field = FileField::new('document');
        $field->maxSize(1048576);
        $fieldDto = $this->configure($field);

        self::assertSame(1048576, $fieldDto->getCustomOption(FileField::OPTION_MAX_SIZE));
    }

    public function testSetMaxSizeWithErrorMessage(): void
    {
        $field = FileField::new('document');
        $field->maxSize('5M', 'File {{ name }} is too large ({{ size }} {{ suffix }})');
        $fieldDto = $this->configure($field);

        self::assertSame('5M', $fieldDto->getCustomOption(FileField::OPTION_MAX_SIZE));
        self::assertSame('File {{ name }} is too large ({{ size }} {{ suffix }})', $fieldDto->getCustomOption(FileField::OPTION_MAX_SIZE_MESSAGE));
    }

    public function testDefaultMaxSizeIsNull(): void
    {
        $field = FileField::new('document');
        $fieldDto = $this->configure($field);

        self::assertNull($fieldDto->getCustomOption(FileField::OPTION_MAX_SIZE));
        self::assertNull($fieldDto->getCustomOption(FileField::OPTION_MAX_SIZE_MESSAGE));
    }

    public function testDefaultViewable(): void
    {
        $field = FileField::new('document');
        $fieldDto = $this->configure($field);

        self::assertTrue($fieldDto->getCustomOption(FileField::OPTION_VIEWABLE));
    }

    public function testIsViewableFalse(): void
    {
        $field = FileField::new('document');
        $field->isViewable(false);
        $fieldDto = $this->configure($field);

        self::assertFalse($fieldDto->getCustomOption(FileField::OPTION_VIEWABLE));
    }

    public function testDefaultDownloadable(): void
    {
        $field = FileField::new('document');
        $fieldDto = $this->configure($field);

        self::assertTrue($fieldDto->getCustomOption(FileField::OPTION_DOWNLOADABLE));
    }

    public function testIsDownloadableFalse(): void
    {
        $field = FileField::new('document');
        $field->isDownloadable(false);
        $fieldDto = $this->configure($field);

        self::assertFalse($fieldDto->getCustomOption(FileField::OPTION_DOWNLOADABLE));
    }

    public function testDefaultReplacedFileBehavior(): void
    {
        $field = FileField::new('document');
        $fieldDto = $this->configure($field);

        self::assertSame(ReplacedFileBehavior::DELETE, $fieldDto->getCustomOption(FileField::OPTION_REPLACED_FILE_BEHAVIOR));
    }

    public function testDeleteReplacedFile(): void
    {
        $field = FileField::new('document');
        $field->deleteReplacedFile();
        $fieldDto = $this->configure($field);

        self::assertSame(ReplacedFileBehavior::DELETE, $fieldDto->getCustomOption(FileField::OPTION_REPLACED_FILE_BEHAVIOR));
    }

    public function testKeepReplacedFile(): void
    {
        $field = FileField::new('document');
        $field->keepReplacedFile();
        $fieldDto = $this->configure($field);

        self::assertSame(ReplacedFileBehavior::KEEP, $fieldDto->getCustomOption(FileField::OPTION_REPLACED_FILE_BEHAVIOR));
    }

    public function testKeepReplacedFileOrFail(): void
    {
        $field = FileField::new('document');
        $field->keepReplacedFileOrFail();
        $fieldDto = $this->configure($field);

        self::assertSame(ReplacedFileBehavior::KEEP_OR_FAIL, $fieldDto->getCustomOption(FileField::OPTION_REPLACED_FILE_BEHAVIOR));
    }

    public function testDefaultDeletable(): void
    {
        $field = FileField::new('document');
        $fieldDto = $this->configure($field);

        self::assertTrue($fieldDto->getCustomOption(FileField::OPTION_DELETABLE));
    }

    public function testIsDeletableFalse(): void
    {
        $field = FileField::new('document');
        $field->isDeletable(false);
        $fieldDto = $this->configure($field);

        self::assertFalse($fieldDto->getCustomOption(FileField::OPTION_DELETABLE));
    }

    public function testIsDeletableTrue(): void
    {
        $field = FileField::new('document');
        $field->isDeletable(false);
        $field->isDeletable(true);
        $fieldDto = $this->configure($field);

        self::assertTrue($fieldDto->getCustomOption(FileField::OPTION_DELETABLE));
    }
}
