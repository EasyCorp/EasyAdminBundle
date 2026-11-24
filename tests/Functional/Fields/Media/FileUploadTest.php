<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Fields\Media;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\AbstractFieldFunctionalTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\FileUploadRestrictionsCrudController;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Component\Filesystem\Filesystem;

class FileUploadTest extends AbstractFieldFunctionalTestCase
{
    private string $uploadDir;
    private string $caseDirectory;

    /**
     * @var string[]
     */
    private array $uploadedPaths = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->catchExceptions(false);
        $this->client->followRedirects(false);

        $this->uploadDir = self::getContainer()->getParameter('kernel.project_dir').'/public/uploads/';
        $this->caseDirectory = 'case_'.bin2hex(random_bytes(6));
        (new Filesystem())->mkdir($this->uploadDir.$this->caseDirectory);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove([
            $this->uploadDir.$this->caseDirectory,
            ...$this->uploadedPaths,
        ]);

        parent::tearDown();
    }

    #[DataProvider('fileDeletionProvider')]
    public function testFileDeletion(string $property, bool $deleteAll, bool $deleteIndividual, bool $uploadReplacement, bool $keepReplacedFiles = false, bool $deleteEveryFile = false): void
    {
        $multiple = \in_array($property, ['multipleFileField', 'multipleImageField'], true);
        $oldPath = $this->caseDirectory.'/old.png';
        $keptPath = $this->caseDirectory.'/kept.png';
        $newPath = $this->caseDirectory.'/new.png';
        $image = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=');
        file_put_contents($this->uploadDir.$oldPath, $image);
        if ($multiple) {
            file_put_contents($this->uploadDir.$keptPath, $image);
        }
        file_put_contents($this->uploadDir.$newPath, $image);

        $entity = $this->createFieldTestEntity([
            $property => $multiple ? [$oldPath, $keptPath] : $oldPath,
            'slugField' => $this->caseDirectory,
        ]);
        $getter = 'get'.ucfirst($property);
        $entityId = $entity->getId();

        $crawler = $this->client->request('GET', $this->getCrudUrl(Action::EDIT, $entityId, ['keepReplacedFiles' => $keepReplacedFiles]));
        static::assertResponseIsSuccessful();
        $form = $crawler->filter('form[name="FieldTestEntity"]')->form();
        $cards = $crawler->filter(sprintf('#FieldTestEntity_%s_file', $property))->closest('[data-ea-fileupload-field]')->filter('.ea-fileupload-card');
        static::assertCount($multiple ? 2 : 1, $cards);
        static::assertSame('old.png', $cards->eq(0)->filter('.ea-fileupload-card-name')->text());
        static::assertStringContainsString('/uploads/'.$oldPath, $cards->eq(0)->filter('.ea-fileupload-action-download')->attr('href'));
        $fieldName = 'FieldTestEntity['.$property.']';
        if ($deleteAll) {
            $form[$fieldName.'[delete]']->tick();
        }
        if ($deleteIndividual) {
            $deletedPaths = [$cards->eq(0)->attr('data-filename')];
            if ($deleteEveryFile) {
                $deletedPaths[] = $cards->eq(1)->attr('data-filename');
            }
            $form[$fieldName.'[deleted_files]'] = json_encode($deletedPaths);
        }
        if ($uploadReplacement) {
            $form[$fieldName.'[file]'.($multiple ? '[0]' : '')]->upload($this->uploadDir.$newPath);
        }

        $this->client->submit($form);

        static::assertResponseRedirects();
        $this->entityManager->clear();
        $savedEntity = $this->fieldTestEntities->find($entityId);
        static::assertNotNull($savedEntity);
        $value = $savedEntity->$getter();
        $paths = (array) $value;
        $newFiles = array_values(array_diff($paths, [$oldPath, $keptPath]));
        $this->uploadedPaths = array_map(fn (string $path): string => $this->uploadDir.$path, $newFiles);
        $oldShouldRemain = !$deleteAll && !$deleteIndividual && ($multiple || !$uploadReplacement);
        static::assertSame($oldShouldRemain || ($keepReplacedFiles && !$deleteAll && !$deleteIndividual), is_file($this->uploadDir.$oldPath));
        static::assertSame($oldShouldRemain, \in_array($oldPath, $paths, true));
        if ($multiple) {
            static::assertSame(!$deleteAll && !$deleteEveryFile, is_file($this->uploadDir.$keptPath));
            static::assertSame(!$deleteAll && !$deleteEveryFile, \in_array($keptPath, $paths, true));
        }
        if ($uploadReplacement) {
            static::assertCount(1, $newFiles);
            static::assertFileExists($this->uploadDir.$newFiles[0]);
        } elseif (!$multiple && ($deleteAll || $deleteIndividual)) {
            static::assertNull($value);
        } elseif ($multiple && ($deleteAll || $deleteEveryFile)) {
            static::assertSame([], $value);
        }
    }

    #[DataProvider('notDeletableFileProvider')]
    public function testSubmittedDeletionIsIgnoredWhenFileIsNotDeletable(string $property, bool $deleteAll, array $replacedFileBehaviorQuery): void
    {
        $multiple = \in_array($property, ['multipleFileField', 'multipleImageField'], true);
        $oldPath = $this->caseDirectory.'/old.png';
        file_put_contents($this->uploadDir.$oldPath, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='));

        $entity = $this->createFieldTestEntity([
            $property => $multiple ? [$oldPath] : $oldPath,
            'slugField' => $this->caseDirectory,
        ]);
        $getter = 'get'.ucfirst($property);
        $entityId = $entity->getId();

        $crawler = $this->client->request('GET', $this->getCrudUrl(Action::EDIT, $entityId, ['notDeletable' => true] + $replacedFileBehaviorQuery));
        static::assertResponseIsSuccessful();
        $form = $crawler->filter('form[name="FieldTestEntity"]')->form();
        $widget = $crawler->filter(sprintf('#FieldTestEntity_%s_file', $property))->closest('[data-ea-fileupload-field]');
        $cards = $widget->filter('.ea-fileupload-card');
        static::assertCount(1, $cards);
        static::assertCount(0, $widget->filter('[data-ea-fileupload-delete-card], [data-ea-fileupload-clear-all], input[type="checkbox"]'));

        // the widget renders no delete controls, so the values they would send are added to the request by hand
        $values = $form->getPhpValues();
        if ($deleteAll) {
            $values['FieldTestEntity'][$property]['delete'] = '1';
        } else {
            $values['FieldTestEntity'][$property]['deleted_files'] = json_encode([$cards->eq(0)->attr('data-filename')]);
        }
        $this->client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        static::assertResponseRedirects();
        $this->entityManager->clear();
        $savedEntity = $this->fieldTestEntities->find($entityId);
        static::assertNotNull($savedEntity);
        static::assertSame($multiple ? [$oldPath] : $oldPath, $savedEntity->$getter());
        static::assertFileExists($this->uploadDir.$oldPath);
    }

    /**
     * A link without the "download" attribute must request the stored file, and
     * only stored files whose type browsers can't run scripts from may be opened
     * inline: any other file (e.g. ".rdf" or ".mk", which web servers send with
     * an XML type or without a Content-Type) is downloaded.
     */
    #[DataProvider('inlineLinkProvider')]
    public function testLinksOpenInlineOnlyTheStoredFileOfASafeType(string $property, string $fileName, bool $opensInline, array $query = []): void
    {
        if ('\\' === \DIRECTORY_SEPARATOR && (str_contains($fileName, '?') || str_ends_with($fileName, '.'))) {
            $this->markTestSkipped('Windows does not allow this file name.');
        }

        $multiple = \in_array($property, ['multipleFileField', 'multipleImageField'], true);
        $storedPath = $this->caseDirectory.'/'.$fileName;
        file_put_contents($this->uploadDir.$this->caseDirectory.'/report.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');
        file_put_contents($this->uploadDir.$storedPath, 'contents');
        $entityId = $this->createFieldTestEntity([
            $property => $multiple ? [$storedPath] : $storedPath,
            'slugField' => $this->caseDirectory,
        ])->getId();

        foreach ([Action::INDEX, Action::DETAIL, Action::EDIT] as $action) {
            $crawler = $this->client->request('GET', $this->getCrudUrl($action, Action::INDEX === $action ? null : $entityId, $query));
            static::assertResponseIsSuccessful();

            $inlineLinks = [];
            foreach ($crawler->filter(sprintf('a[href*="/uploads/%s/"]', $this->caseDirectory)) as $link) {
                if ($link->hasAttribute('download')) {
                    continue;
                }

                $href = $link->getAttribute('href');
                static::assertSame('/uploads/'.$storedPath, rawurldecode(parse_url($href, \PHP_URL_PATH)), sprintf('The inline link "%s" of the %s page requests another file.', $href, $action));
                $inlineLinks[] = $href;
            }

            static::assertSame($opensInline, [] !== $inlineLinks, sprintf('The %s page links "%s" inline: %s.', $action, $fileName, json_encode($inlineLinks)));
        }
    }

    public static function inlineLinkProvider(): iterable
    {
        yield 'text file' => ['fileField', 'notes.txt', true];
        yield 'PDF document' => ['fileField', 'document.pdf', true];
        yield 'question mark in the name' => ['fileField', 'report.svg?.txt', true];
        yield 'hash in the name' => ['fileField', 'report.svg#.txt', true];
        yield 'question mark in the name of a multiple field' => ['multipleFileField', 'report.svg?.txt', true];
        yield 'SVG image' => ['fileField', 'report.svg', false];
        yield 'served with an XML type' => ['fileField', 'resource-map.rdf', false];
        yield 'unknown to web servers' => ['fileField', 'build-notes.mk', false];
        yield 'no extension' => ['fileField', 'README', false];
        yield 'empty extension' => ['fileField', 'report.html.', false];
        yield 'risky inline render' => ['fileField', 'build-notes.mk', true, ['allowRiskyInlineRender' => true]];
        yield 'raster image' => ['imageField', 'photo.png', true];
        yield 'question mark in the name of an image' => ['imageField', 'report.svg?.png', true];
        yield 'SVG image in an image field' => ['imageField', 'report.svg', false];
        yield 'image field with an unknown type' => ['imageField', 'photo.heic', false];
    }

    #[TestWith(['fileField', ['mimeTypes' => 'application/pdf'], 'document.pdf', 'The mime type of the file is invalid'])]
    #[TestWith(['fileField', ['mimeTypes' => '.pdf'], 'document.pdf', 'The mime type of the file is invalid'])]
    #[TestWith(['fileField', ['maxSize' => 10], 'document.pdf', 'The file is too large'])]
    #[TestWith(['multipleFileField', ['mimeTypes' => '.pdf'], 'document.pdf', 'The mime type of the file is invalid'])]
    #[TestWith(['imageField', [], 'image.png', 'This file is not a valid image.'])]
    public function testUploadRestrictionsApplyWhenValidationGroupsLeaveOutDefault(string $property, array $restrictions, string $uploadedFileName, string $expectedError): void
    {
        $this->assertUploadIsRejected($property, ['validationGroup' => 'custom_group'] + $restrictions, $uploadedFileName, $expectedError);
    }

    #[TestWith(['fileField', '.ofx', 'statement.html', 'The extension of the file is invalid'])]
    #[TestWith(['fileField', '.ofx,.cfg', 'statement.html', 'The extension of the file is invalid'])]
    #[TestWith(['multipleFileField', '.ofx', 'statement.html', 'The extension of the file is invalid'])]
    #[TestWith(['imageField', '.ofx', 'image.png', 'The extension of the file is invalid'])]
    public function testMimeTypesWithUnknownExtensionsRestrictTheFileExtension(string $property, string $mimeTypes, string $uploadedFileName, string $expectedError): void
    {
        $this->assertUploadIsRejected($property, ['mimeTypes' => $mimeTypes], $uploadedFileName, $expectedError);
    }

    #[TestWith(['fileField', 'OFXHEADER:100', 'txt'])]
    #[TestWith(['fileField', '<!DOCTYPE html><html><body>File 1</body></html>', 'html'])]
    #[TestWith(['multipleFileField', '<!DOCTYPE html><html><body>File 1</body></html>', 'html'])]
    public function testMimeTypesWithUnknownExtensionsStoreFilesWithTheAllowedExtension(string $property, string $contents, string $guessedExtension): void
    {
        $uploadedPath = $this->uploadDir.$this->caseDirectory.'/'.$this->caseDirectory.'.ofx';
        file_put_contents($uploadedPath, $contents);
        $storedPath = $this->uploadDir.$this->caseDirectory.'.ofx';
        $guessedPath = $this->uploadDir.$this->caseDirectory.'.'.$guessedExtension;
        $this->uploadedPaths = [$storedPath, $guessedPath];

        $crawler = $this->client->request('GET', $this->getCrudUrl(Action::NEW, null, ['mimeTypes' => '.ofx'], controllerFqcn: FileUploadRestrictionsCrudController::class));
        $form = $crawler->filter('form[name="FieldTestEntity"]')->form();
        $form['FieldTestEntity['.$property.'][file]'.('multipleFileField' === $property ? '[0]' : '')]->upload($uploadedPath);
        $this->client->submit($form);

        static::assertResponseRedirects();
        static::assertSame(1, $this->fieldTestEntities->count([]));
        static::assertFileExists($storedPath);
        static::assertFileDoesNotExist($guessedPath);
    }

    private function assertUploadIsRejected(string $property, array $query, string $uploadedFileName, string $expectedError): void
    {
        $uploadedPath = $this->uploadDir.$this->caseDirectory.'/'.$uploadedFileName;
        file_put_contents($uploadedPath, '<!DOCTYPE html><html><body>File 1</body></html>');
        // the default file name pattern guesses the extension from the file contents
        $storedPath = $this->uploadDir.pathinfo($uploadedFileName, \PATHINFO_FILENAME).'.html';
        $this->uploadedPaths = [$storedPath];

        $crawler = $this->client->request('GET', $this->getCrudUrl(Action::NEW, null, $query, controllerFqcn: FileUploadRestrictionsCrudController::class));
        static::assertResponseIsSuccessful();
        $form = $crawler->filter('form[name="FieldTestEntity"]')->form();
        $isMultiple = 'multipleFileField' === $property;
        $form['FieldTestEntity['.$property.'][file]'.($isMultiple ? '[0]' : '')]->upload($uploadedPath);
        $this->client->submit($form);

        static::assertResponseStatusCodeSame(422);
        static::assertStringContainsString($expectedError, implode(' ', $this->client->getCrawler()->filter('.invalid-feedback')->each(static fn ($node): string => $node->text())));
        static::assertSame(0, $this->fieldTestEntities->count([]));
        static::assertFileDoesNotExist($storedPath);
    }

    public static function notDeletableFileProvider(): iterable
    {
        $replacedFileBehaviors = [
            'delete policy' => [],
            'keep policy' => ['keepReplacedFiles' => true],
            'keep or fail policy' => ['keepReplacedFilesOrFail' => true],
        ];

        foreach (['fileField', 'multipleFileField', 'imageField', 'multipleImageField'] as $property) {
            foreach ($replacedFileBehaviors as $policy => $query) {
                yield $property.' delete all with '.$policy => [$property, true, $query];
                yield $property.' individual deletion with '.$policy => [$property, false, $query];
            }
        }
    }

    public static function fileDeletionProvider(): iterable
    {
        foreach (['fileField', 'multipleFileField', 'imageField', 'multipleImageField'] as $property) {
            yield $property.' unchanged' => [$property, false, false, false];
            yield $property.' individual deletion' => [$property, false, true, false];
            yield $property.' individual deletion with replacement' => [$property, false, true, true];
            yield $property.' delete all' => [$property, true, false, false];
            yield $property.' delete all with replacement' => [$property, true, false, true];
            yield $property.' delete all and individual deletion' => [$property, true, true, false];
            yield $property.' delete all and individual deletion with replacement' => [$property, true, true, true];
            yield $property.' upload' => [$property, false, false, true];
            yield $property.' individual deletion with keep policy' => [$property, false, true, false, true];
            yield $property.' individual deletion with replacement and keep policy' => [$property, false, true, true, true];
            yield $property.' upload with keep policy' => [$property, false, false, true, true];
            if (\in_array($property, ['multipleFileField', 'multipleImageField'], true)) {
                yield $property.' delete every file individually' => [$property, false, true, false, false, true];
            }
        }
    }
}
