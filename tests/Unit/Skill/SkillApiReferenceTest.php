<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Skill;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Skill\Support\ApiReferenceGenerator;
use PHPUnit\Framework\TestCase;

class SkillApiReferenceTest extends TestCase
{
    private const OUT_OF_DATE_MESSAGE = 'skills/easyadmin/references/api.md is out of date. Run "make skill-api-reference" and commit the result.';

    public function testApiReferenceMatchesTheSourceCode(): void
    {
        $packageDir = \dirname(__DIR__, 3);
        $apiReferencePath = $packageDir.'/skills/easyadmin/references/api.md';

        if (!is_file($apiReferencePath)) {
            $this->fail(self::OUT_OF_DATE_MESSAGE);
        }

        $committedContents = file_get_contents($apiReferencePath);
        if (false === $committedContents) {
            $this->fail(sprintf('The "%s" file cannot be read.', $apiReferencePath));
        }

        $generatedContents = (new ApiReferenceGenerator($packageDir))->generate();

        $this->assertSame($this->normalizeLineEndings($generatedContents), $this->normalizeLineEndings($committedContents), self::OUT_OF_DATE_MESSAGE);
    }

    private function normalizeLineEndings(string $contents): string
    {
        return str_replace("\r\n", "\n", $contents);
    }
}
