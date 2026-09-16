<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Skill;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Skill\Support\SkillFile;
use PHPUnit\Framework\TestCase;

class SkillPackagingTest extends TestCase
{
    private const LINE_ENDINGS_ATTRIBUTE = 'skills/**/*.md text eol=lf';
    private const CONTRIBUTOR_SKILLS_PATTERN = '/.claude/skills/*/SKILL.md';
    private const INTERNAL_METADATA_KEY = 'internal';

    public static function provideContributorSkills(): iterable
    {
        foreach (glob(\dirname(__DIR__, 3).self::CONTRIBUTOR_SKILLS_PATTERN) ?: [] as $path) {
            yield basename(\dirname($path)) => [$path];
        }
    }

    public function testTheSkillIsShippedInsideTheComposerPackage(): void
    {
        $exportIgnoredSkills = [];
        foreach (self::gitAttributesLines() as $line) {
            if (!str_contains($line, 'export-ignore')) {
                continue;
            }

            $pattern = ltrim(preg_split('/\s+/', $line)[0], '/');
            if (str_starts_with($pattern, 'skills')) {
                $exportIgnoredSkills[] = $line;
            }
        }

        self::assertSame([], $exportIgnoredSkills, sprintf('These .gitattributes lines keep the agent skill out of the Composer package: %s. Without the skill in vendor/easycorp/easyadmin-bundle/skills/, the install command has nothing to copy.', implode(', ', $exportIgnoredSkills)));
    }

    public function testSkillFilesKeepUnixLineEndingsOnEveryPlatform(): void
    {
        self::assertContains(self::LINE_ENDINGS_ATTRIBUTE, self::gitAttributesLines(), sprintf('.gitattributes must contain the line "%s". Otherwise a Windows checkout rewrites the skill with CRLF endings and the installed copies never match the shipped ones byte for byte.', self::LINE_ENDINGS_ATTRIBUTE));
    }

    public function testContributorSkillsExist(): void
    {
        $paths = iterator_to_array(self::provideContributorSkills(), false);

        self::assertNotSame([], $paths, sprintf('No contributor skill was found at "%s". Update this test if they moved.', ltrim(self::CONTRIBUTOR_SKILLS_PATTERN, '/')));
    }

    /**
     * @dataProvider provideContributorSkills
     */
    public function testContributorSkillsAreMarkedAsInternal(string $path): void
    {
        $metadata = SkillFile::fromPath($path)->metadata();
        $relativePath = ltrim(str_replace(\dirname(__DIR__, 3), '', $path), '/');

        self::assertArrayHasKey(self::INTERNAL_METADATA_KEY, $metadata, sprintf('The front matter of %s must declare "%s: true" under "metadata:", so that "npx skills add" skips a skill written for contributors of this repository and offers only the skill meant for EasyAdmin users.', $relativePath, self::INTERNAL_METADATA_KEY));
        self::assertSame('true', $metadata[self::INTERNAL_METADATA_KEY], sprintf('The "metadata.%s" key of %s must be exactly "true".', self::INTERNAL_METADATA_KEY, $relativePath));
    }

    /**
     * @return list<string>
     */
    private static function gitAttributesLines(): array
    {
        $path = \dirname(__DIR__, 3).'/.gitattributes';
        $contents = file_get_contents($path);
        self::assertNotFalse($contents, sprintf('The file "%s" cannot be read.', $path));

        $lines = array_map('trim', explode("\n", $contents));

        return array_values(array_filter($lines, static fn (string $line): bool => '' !== $line));
    }
}
