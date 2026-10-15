<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Fields\Media;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\AbstractFieldFunctionalTest;
use Symfony\Component\Filesystem\Filesystem;

class FileUploadTest extends AbstractFieldFunctionalTest
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

    /**
     * @dataProvider fileDeletionProvider
     */
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
