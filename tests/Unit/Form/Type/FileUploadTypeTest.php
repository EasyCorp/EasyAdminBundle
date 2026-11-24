<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Form\Type;

use EasyCorp\Bundle\EasyAdminBundle\Form\Type\FileUploadType;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Extension\Validator\ViolationMapper\ViolationMapperInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\GroupSequence;
use Symfony\Component\Validator\Validation;

class FileUploadTypeTest extends TypeTestCase
{
    private string $projectDir;
    private string $uploadDir;

    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir().'/ea_test_project_'.bin2hex(random_bytes(4));
        $this->uploadDir = $this->projectDir.'/public/uploads/files';
        mkdir($this->uploadDir, 0777, true);
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        (new Filesystem())->remove($this->projectDir);
    }

    protected function getExtensions(?ViolationMapperInterface $violationMapper = null): array
    {
        return [
            new PreloadedExtension([new FileUploadType($this->projectDir, new Filesystem())], []),
            new ValidatorExtension(Validation::createValidator()),
            new HttpFoundationExtension(),
        ];
    }

    /**
     * @dataProvider provideParentValidationGroups
     */
    public function testFileConstraintsRunWithTheValidationGroupsOfTheParentForm(mixed $parentValidationGroups, array $options, bool $expectedIsValid): void
    {
        $form = $this->factory->createBuilder(FormType::class, null, ['validation_groups' => $parentValidationGroups])
            ->add('document', FileUploadType::class, $options + [
                'upload_dir' => $this->uploadDir,
                'file_constraints' => [new File(mimeTypes: ['application/pdf'])],
            ])
            ->getForm();

        $filePath = $this->projectDir.'/document.pdf';
        file_put_contents($filePath, '<!DOCTYPE html><html><body>Document 1</body></html>');
        $file = new UploadedFile($filePath, 'document.pdf', 'application/pdf', null, true);

        $form->submit(['document' => ['file' => ($options['multiple'] ?? false) ? [$file] : $file]]);

        $this->assertSame($expectedIsValid, $form->isValid());
        $this->assertCount($expectedIsValid ? 0 : 1, $form->get('document')->getErrors(true));
    }

    public static function provideParentValidationGroups(): iterable
    {
        yield 'no groups' => [null, [], false];
        yield 'Default group' => [['Default'], [], false];
        yield 'custom group' => [['group1'], [], false];
        yield 'custom group and multiple files' => [['group1'], ['multiple' => true], false];
        yield 'custom group as a string' => ['group1', [], false];
        yield 'custom group returned by a callable' => [static fn (FormInterface $form): array => ['group1'], [], false];
        yield 'group sequence' => [new GroupSequence(['group1', ['group2', 'group3']]), [], false];
        yield 'validation disabled' => [false, [], true];
        yield 'groups defined in the field' => [['group1'], ['validation_groups' => ['group1']], true];
    }
}
