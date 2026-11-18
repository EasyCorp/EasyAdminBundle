<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Fields\Media;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\AbstractFieldFunctionalTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Upload\DatedDirectoryUploadCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Upload\EntityPropertyDirectoryUploadCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Upload\FlysystemNameAsDirectoryUploadCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Upload\NameAsDirectoryUploadCrudController;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * The name of an uploaded file can come from values that the backend user controls
 * (the client file name, other properties of the entity). Whatever the upload
 * name pattern produces, the file must be written inside the upload dir.
 */
class UploadPathTest extends AbstractFieldFunctionalTestCase
{
    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=';

    private string $projectDir;
    private string $sourceDir;
    private string $flysystemRoot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->catchExceptions(false);
        $this->client->followRedirects(false);

        $this->projectDir = self::getContainer()->getParameter('kernel.project_dir');
        $this->flysystemRoot = self::getContainer()->getParameter('test.flysystem_storage_dir');
        $this->sourceDir = sys_get_temp_dir().'/upload_path_'.bin2hex(random_bytes(6));
        (new Filesystem())->mkdir($this->sourceDir);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove([
            $this->sourceDir,
            $this->flysystemRoot,
            ...array_map(fn (string $uploadDir): string => \dirname($this->projectDir.'/'.$uploadDir), [
                NameAsDirectoryUploadCrudController::UPLOAD_DIR,
                DatedDirectoryUploadCrudController::UPLOAD_DIR,
                EntityPropertyDirectoryUploadCrudController::UPLOAD_DIR,
            ]),
        ]);

        parent::tearDown();
    }

    #[TestWith(['fileField', '...txt'])]
    #[TestWith(['multipleFileField', '...txt'])]
    #[TestWith(['imageField', '...png'])]
    public function testClientFileNameCannotMoveTheUploadOutOfTheUploadDir(string $property, string $clientFileName): void
    {
        [$sourcePath, $hash] = $this->createSourceFile($clientFileName);
        $uploadDir = $this->projectDir.'/'.NameAsDirectoryUploadCrudController::UPLOAD_DIR;
        $extension = pathinfo($clientFileName, \PATHINFO_EXTENSION);

        $form = $this->getForm(NameAsDirectoryUploadCrudController::class, Action::NEW);
        $form[$this->getFileInputName($property)]->upload($sourcePath);
        $this->client->submit($form);

        static::assertFileDoesNotExist(\dirname($uploadDir).'/'.$hash.'.'.$extension);
        static::assertResponseStatusCodeSame(422);
        static::assertSelectorExists('.invalid-feedback');
        $this->entityManager->clear();
        static::assertCount(0, $this->fieldTestEntities->findAll());
    }

    public function testNameAsDirectoryKeepsWorkingForOrdinaryFileNames(): void
    {
        [$sourcePath, $hash] = $this->createSourceFile('notes.txt');

        $form = $this->getForm(NameAsDirectoryUploadCrudController::class, Action::NEW);
        $form[$this->getFileInputName('fileField')]->upload($sourcePath);
        $this->client->submit($form);

        static::assertResponseRedirects();
        $entity = $this->findOnlyEntity();
        static::assertSame('notes/'.$hash.'.txt', $entity->getFileField());
        static::assertFileExists($this->projectDir.'/'.NameAsDirectoryUploadCrudController::UPLOAD_DIR.'notes/'.$hash.'.txt');
    }

    public function testEntityPropertyUsedAsDirectoryCannotMoveTheUploadOutOfTheUploadDir(): void
    {
        [$sourcePath, $hash] = $this->createSourceFile('notes.txt');
        $uploadDir = $this->projectDir.'/'.EntityPropertyDirectoryUploadCrudController::UPLOAD_DIR;
        // the value saved by a previous edit of the entity
        $entity = $this->createFieldTestEntity(['textField' => '..']);

        $form = $this->getForm(EntityPropertyDirectoryUploadCrudController::class, Action::EDIT, $entity->getId());
        $form[$this->getFileInputName('fileField')]->upload($sourcePath);
        $this->client->submit($form);

        static::assertFileDoesNotExist(\dirname($uploadDir).'/'.$hash.'.txt');
        static::assertResponseStatusCodeSame(422);
        static::assertSelectorExists('.invalid-feedback');
        $this->entityManager->clear();
        static::assertNull($this->fieldTestEntities->find($entity->getId())->getFileField());
    }

    public function testUploadDirInsideTheGeneratedNameCannotMoveTheUploadOutOfTheUploadDir(): void
    {
        [$sourcePath, $hash] = $this->createSourceFile('notes.txt');
        $uploadDir = $this->projectDir.'/'.EntityPropertyDirectoryUploadCrudController::UPLOAD_DIR;
        $entity = $this->createFieldTestEntity(['textField' => '.'.$uploadDir.'.']);

        $form = $this->getForm(EntityPropertyDirectoryUploadCrudController::class, Action::EDIT, $entity->getId());
        $form[$this->getFileInputName('fileField')]->upload($sourcePath);
        try {
            $this->client->submit($form);
        } catch (FileException $e) {
            // on Windows the absolute upload dir starts with a drive letter (e.g. 'D:\') and
            // ':' is not valid inside a directory name, so the file can't be written at all
            if ('\\' !== \DIRECTORY_SEPARATOR) {
                throw $e;
            }
        }

        static::assertFileDoesNotExist(\dirname($uploadDir).'/'.$hash.'.txt');
        $this->entityManager->clear();
        $storedPath = $this->fieldTestEntities->find($entity->getId())->getFileField();
        if (null !== $storedPath) {
            static::assertFileExists($uploadDir.$storedPath);
        }
    }

    public function testUploadDirInsideTheGeneratedNameCannotMoveTheUploadOutOfTheFlysystemUploadDir(): void
    {
        [$sourcePath, $hash] = $this->createSourceFile('notes.txt');
        $entity = $this->createFieldTestEntity(['textField' => '.'.FlysystemNameAsDirectoryUploadCrudController::UPLOAD_DIR.'.']);

        $form = $this->getForm(FlysystemNameAsDirectoryUploadCrudController::class, Action::EDIT, $entity->getId());
        $form[$this->getFileInputName('multipleFileField')]->upload($sourcePath);
        $this->client->submit($form);

        static::assertFileDoesNotExist($this->flysystemRoot.'/'.$hash.'.txt');
        $this->entityManager->clear();
        foreach ($this->fieldTestEntities->find($entity->getId())->getMultipleFileField() as $storedPath) {
            static::assertFileExists($this->flysystemRoot.'/'.FlysystemNameAsDirectoryUploadCrudController::UPLOAD_DIR.$storedPath);
        }
    }

    #[TestWith(['fileField', '...txt'])]
    #[TestWith(['multipleFileField', '...txt'])]
    public function testClientFileNameCannotMoveTheUploadOutOfTheFlysystemUploadDir(string $property, string $clientFileName): void
    {
        [$sourcePath, $hash] = $this->createSourceFile($clientFileName);

        $form = $this->getForm(FlysystemNameAsDirectoryUploadCrudController::class, Action::NEW);
        $form[$this->getFileInputName($property)]->upload($sourcePath);
        $this->client->submit($form);

        static::assertFileDoesNotExist($this->flysystemRoot.'/'.$hash.'.txt');
        static::assertResponseStatusCodeSame(422);
        static::assertSelectorExists('.invalid-feedback');
        $this->entityManager->clear();
        static::assertCount(0, $this->fieldTestEntities->findAll());
    }

    public function testFlysystemUploadKeepsWorkingForOrdinaryFileNames(): void
    {
        [$sourcePath, $hash] = $this->createSourceFile('notes.txt');

        $form = $this->getForm(FlysystemNameAsDirectoryUploadCrudController::class, Action::NEW);
        $form[$this->getFileInputName('multipleFileField')]->upload($sourcePath);
        $this->client->submit($form);

        static::assertResponseRedirects();
        static::assertSame(['notes/'.$hash.'.txt'], $this->findOnlyEntity()->getMultipleFileField());
        static::assertFileExists($this->flysystemRoot.'/'.FlysystemNameAsDirectoryUploadCrudController::UPLOAD_DIR.'notes/'.$hash.'.txt');
    }

    public function testSubdirectoriesInTheNamePatternKeepWorking(): void
    {
        [$sourcePath] = $this->createSourceFile('notes.txt');
        $uploadDir = $this->projectDir.'/'.DatedDirectoryUploadCrudController::UPLOAD_DIR;
        $expectedPath = date('Y').'/'.date('m').'/notes.txt';
        (new Filesystem())->dumpFile($uploadDir.$expectedPath, 'Existing file');

        $form = $this->getForm(DatedDirectoryUploadCrudController::class, Action::NEW);
        $form[$this->getFileInputName('fileField')]->upload($sourcePath);
        $this->client->submit($form);

        static::assertResponseRedirects();
        $renamedPath = date('Y').'/'.date('m').'/notes_1.txt';
        static::assertSame($renamedPath, $this->findOnlyEntity()->getFileField());
        static::assertFileExists($uploadDir.$renamedPath);
        static::assertStringEqualsFile($uploadDir.$expectedPath, 'Existing file');
    }

    public function testDefaultNamePatternKeepsUnusualClientFileNames(): void
    {
        [$sourcePath] = $this->createSourceFile('...txt');

        $form = $this->getForm(DatedDirectoryUploadCrudController::class, Action::NEW);
        $form[$this->getFileInputName('multipleFileField')]->upload($sourcePath);
        $this->client->submit($form);

        static::assertResponseRedirects();
        static::assertSame(['...txt'], $this->findOnlyEntity()->getMultipleFileField());
        static::assertFileExists($this->projectDir.'/'.DatedDirectoryUploadCrudController::UPLOAD_DIR.'...txt');
    }

    public function testDefaultNamePatternRejectsParentDirectoryAsName(): void
    {
        $sourcePath = $this->sourceDir.'/source.tmp';
        // a content without known extension, so the default pattern '[name].[extension]' generates '..'
        file_put_contents($sourcePath, '<?php echo 1;');

        $form = $this->getForm(DatedDirectoryUploadCrudController::class, Action::NEW);
        $this->client->request('POST', $form->getUri(), $form->getPhpValues(), [
            'FieldTestEntity' => ['multipleFileField' => ['file' => [new UploadedFile($sourcePath, '..', null, null, true)]]],
        ]);

        static::assertResponseStatusCodeSame(422);
        static::assertSelectorExists('.invalid-feedback');
        $this->entityManager->clear();
        static::assertCount(0, $this->fieldTestEntities->findAll());
    }

    public function testDeletedFilesOnlySelectsStoredFiles(): void
    {
        $uploadDir = $this->projectDir.'/'.NameAsDirectoryUploadCrudController::UPLOAD_DIR;
        $filesystem = new Filesystem();
        $filesystem->dumpFile($uploadDir.'kept.txt', 'Stored file');
        $filesystem->dumpFile(\dirname($uploadDir).'/outside.txt', 'File outside the upload dir');
        $entity = $this->createFieldTestEntity(['multipleFileField' => ['kept.txt']]);

        $form = $this->getForm(NameAsDirectoryUploadCrudController::class, Action::EDIT, $entity->getId());
        $form['FieldTestEntity[multipleFileField][deleted_files]'] = json_encode(['../outside.txt', \dirname($uploadDir).'/outside.txt']);
        $this->client->submit($form);

        static::assertResponseRedirects();
        static::assertFileExists(\dirname($uploadDir).'/outside.txt');
        static::assertFileExists($uploadDir.'kept.txt');
        $this->entityManager->clear();
        static::assertSame(['kept.txt'], $this->fieldTestEntities->find($entity->getId())->getMultipleFileField());
    }

    /**
     * @return array{string, string} the path of the source file and the hash of its contents
     */
    private function createSourceFile(string $clientFileName): array
    {
        $contents = 'png' === pathinfo($clientFileName, \PATHINFO_EXTENSION)
            ? base64_decode(self::PNG)
            : 'Upload '.bin2hex(random_bytes(8));
        $path = $this->sourceDir.'/'.$clientFileName;
        file_put_contents($path, $contents);

        return [$path, sha1($contents)];
    }

    private function getForm(string $controllerFqcn, string $action, ?int $entityId = null): Form
    {
        $crawler = $this->client->request('GET', $this->getCrudUrl($action, $entityId, controllerFqcn: $controllerFqcn));
        static::assertResponseIsSuccessful();

        return $crawler->filter('form[name="FieldTestEntity"]')->form();
    }

    private function getFileInputName(string $property): string
    {
        return 'FieldTestEntity['.$property.'][file]'.('multipleFileField' === $property ? '[0]' : '');
    }

    private function findOnlyEntity(): object
    {
        $this->entityManager->clear();
        $entities = $this->fieldTestEntities->findAll();
        static::assertCount(1, $entities);

        return $entities[0];
    }
}
