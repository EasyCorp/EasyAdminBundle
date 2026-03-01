<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Fields\Text;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\AbstractFieldFunctionalTest;
use Symfony\Component\DomCrawler\Crawler;

class CodeEditorFieldTest extends AbstractFieldFunctionalTest
{
    public function testCodeEditorFieldDisplaysOnIndex(): void
    {
        $code = '<?php echo "Hello World";';
        $entity = $this->createFieldTestEntity([
            'codeEditorField' => $code,
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());

        $entityRow = $crawler->filter(sprintf('tr[data-id="%d"]', $entity->getId()));
        static::assertCount(1, $entityRow, 'Entity row should exist');

        $codeEditorFieldCell = $entityRow->filter('td[data-column="codeEditorField"]');
        // code is usually truncated on index, but should contain part of it
        $cellText = $codeEditorFieldCell->text();
        static::assertTrue(
            str_contains($cellText, 'echo') || str_contains($cellText, 'Hello') || str_contains($cellText, 'php'),
            sprintf('CodeEditorField should display code snippet, got: %s', $cellText)
        );
    }

    public function testCodeEditorFieldDisplaysOnDetail(): void
    {
        $code = "function test() {\n    return true;\n}";
        $entity = $this->createFieldTestEntity([
            'codeEditorField' => $code,
        ]);

        $crawler = $this->client->request('GET', $this->generateDetailUrl($entity->getId()));

        $fieldGroups = $crawler->filter('.content-body .field-group');
        $codeEditorFieldFound = false;

        foreach ($fieldGroups as $fieldGroup) {
            $groupCrawler = new Crawler($fieldGroup);
            $label = $groupCrawler->filter('.field-label')->text();

            if (false !== stripos($label, 'CodeEditorField') || false !== stripos($label, 'Code Editor') || false !== stripos($label, 'CodeEditor')) {
                $codeEditorFieldFound = true;
                $fieldValue = $groupCrawler->filter('.field-value')->text();
                static::assertStringContainsString('function', $fieldValue);
                static::assertStringContainsString('return', $fieldValue);
                break;
            }
        }

        static::assertTrue($codeEditorFieldFound, 'CodeEditorField should be displayed on detail page');
    }

    public function testCodeEditorFieldInForm(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $form = $crawler->filter('form[name="FieldTestEntity"]');
        static::assertCount(1, $form, 'Form should exist');

        // codeEditorField renders as a textarea with special classes for CodeMirror/EasyMDE
        $codeEditorFieldInput = $crawler->filter('#FieldTestEntity_codeEditorField');
        static::assertCount(1, $codeEditorFieldInput, 'CodeEditorField input should exist in form');
        static::assertSame('textarea', $codeEditorFieldInput->nodeName(), 'CodeEditorField should be a textarea');
    }

    public function testCodeEditorFieldSubmission(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $form = $crawler->filter('form[name="FieldTestEntity"]')->form();
        $code = '<?php namespace App; class Test {}';
        $form['FieldTestEntity[codeEditorField]'] = $code;
        $form['FieldTestEntity[slugField]'] = 'code-test';

        $this->client->submit($form);

        $entity = $this->fieldTestEntities->findOneBy([], ['id' => 'DESC']);
        static::assertNotNull($entity, 'Entity should be created');
        static::assertSame($code, $entity->getCodeEditorField());
    }

    public function testCodeEditorFieldEdit(): void
    {
        $originalCode = 'const x = 1;';
        $entity = $this->createFieldTestEntity([
            'codeEditorField' => $originalCode,
            'slugField' => 'code-edit-test',
        ]);

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($entity->getId()));

        $form = $crawler->filter('form[name="FieldTestEntity"]')->form();
        static::assertSame($originalCode, $form['FieldTestEntity[codeEditorField]']->getValue());

        $newCode = 'const x = 2; const y = 3;';
        $form['FieldTestEntity[codeEditorField]'] = $newCode;
        $this->client->submit($form);

        $this->entityManager->clear();
        $updatedEntity = $this->fieldTestEntities->find($entity->getId());
        static::assertSame($newCode, $updatedEntity->getCodeEditorField());
    }

    public function testCodeEditorFieldWithNullValue(): void
    {
        $entity = $this->createFieldTestEntity([
            'codeEditorField' => null,
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());

        $entityRow = $crawler->filter(sprintf('tr[data-id="%d"]', $entity->getId()));
        static::assertCount(1, $entityRow, 'Entity row should exist');

        $codeEditorFieldCell = $entityRow->filter('td[data-column="codeEditorField"]');
        static::assertCount(1, $codeEditorFieldCell, 'CodeEditor field cell should exist even with null value');
    }

    public function testCodeEditorFieldWithMultilineCode(): void
    {
        $multilineCode = <<<'CODE'
<?php

namespace App\Controller;

class HomeController
{
    public function index(): Response
    {
        return $this->render('home/index.html.twig');
    }
}
CODE;

        $entity = $this->createFieldTestEntity([
            'codeEditorField' => $multilineCode,
            'slugField' => 'multiline-code',
        ]);

        $crawler = $this->client->request('GET', $this->generateDetailUrl($entity->getId()));

        $html = $crawler->html();
        static::assertStringContainsString('HomeController', $html);
        static::assertStringContainsString('namespace', $html);
    }
}
