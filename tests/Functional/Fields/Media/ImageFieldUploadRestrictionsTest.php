<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Fields\Media;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\AbstractFieldFunctionalTest;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\FileUploadRestrictionsCrudController;
use Symfony\Component\Filesystem\Filesystem;

class ImageFieldUploadRestrictionsTest extends AbstractFieldFunctionalTest
{
    private string $uploadDir;
    private string $sourceDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->uploadDir = static::$kernel->getProjectDir().'/public/uploads/upload_restrictions/';
        $this->sourceDir = sys_get_temp_dir().'/ea_upload_restrictions_'.bin2hex(random_bytes(4));
        (new Filesystem())->mkdir($this->sourceDir);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove([$this->uploadDir, $this->sourceDir]);

        parent::tearDown();
    }

    protected function getControllerFqcn(): string
    {
        return FileUploadRestrictionsCrudController::class;
    }

    /**
     * @dataProvider provideRestrictions
     */
    public function testUploadRestrictionsApplyWhenValidationGroupsLeaveOutDefault(array $restrictions, string $expectedError): void
    {
        $entityCount = $this->fieldTestEntities->count([]);
        $uploadedPath = $this->sourceDir.'/image.png';
        file_put_contents($uploadedPath, '<!DOCTYPE html><html><body>File 1</body></html>');

        $crawler = $this->client->request('GET', $this->getCrudUrl(Action::NEW, null, ['validationGroup' => 'custom_group'] + $restrictions));
        static::assertResponseIsSuccessful();
        $form = $crawler->filter('form[name="FieldTestEntity"]')->form();
        $form['FieldTestEntity[imageField][file]']->upload($uploadedPath);
        $this->client->submit($form);

        static::assertResponseStatusCodeSame(422);
        static::assertStringContainsString($expectedError, implode(' ', $this->client->getCrawler()->filter('.invalid-feedback')->each(static fn ($node): string => $node->text())));
        static::assertSame($entityCount, $this->fieldTestEntities->count([]));
        static::assertFileDoesNotExist($this->uploadDir.'image.png');
    }

    public static function provideRestrictions(): iterable
    {
        yield 'default image constraint' => [[], 'This file is not a valid image.'];
        yield 'custom file constraint' => [['maxSize' => 10], 'The file is too large'];
    }
}
