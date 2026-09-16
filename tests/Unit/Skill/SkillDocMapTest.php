<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Skill;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Skill\Support\SkillFile;
use PHPUnit\Framework\TestCase;

class SkillDocMapTest extends TestCase
{
    private const CLASSES_WITHOUT_DOC_PAGE = ['Field', 'FormField'];

    public function testEveryFieldClassHasItsOwnDocumentationPage(): void
    {
        $documentedFields = self::basenames(\dirname(__DIR__, 3).'/doc/fields/*.rst', '.rst');
        $fieldClasses = array_values(array_diff(self::basenames(\dirname(__DIR__, 3).'/src/Field/*Field.php', '.php'), self::CLASSES_WITHOUT_DOC_PAGE));

        sort($documentedFields);
        sort($fieldClasses);

        self::assertSame($fieldClasses, $documentedFields, sprintf('The field classes in src/Field/ and the pages in doc/fields/ no longer match. A new field needs its own doc/fields/<Name>Field.rst page and a mention in %s, and a field that disappears must lose both. "%s" are the only classes without a page.', SkillFile::RELATIVE_PATH, implode('" and "', self::CLASSES_WITHOUT_DOC_PAGE)));
    }

    /**
     * @return list<string>
     */
    private static function basenames(string $pattern, string $extension): array
    {
        return array_map(static fn (string $path): string => basename($path, $extension), glob($pattern) ?: []);
    }
}
