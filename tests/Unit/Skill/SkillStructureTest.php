<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Skill;

use EasyCorp\Bundle\EasyAdminBundle\Ai\SkillInstaller;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Skill\Support\SkillFile;
use PHPUnit\Framework\TestCase;

class SkillStructureTest extends TestCase
{
    private const MAX_SKILL_LINES = 500;
    // the Agent Skills specification recommends a body under 5,000 tokens; this
    // byte limit is an estimate of that (about 4 bytes per token), not a token count
    private const MAX_INSTALLED_BODY_BYTES = 20000;
    private const MAX_REFERENCE_LINES_WITHOUT_CONTENTS = 100;
    private const TABLE_OF_CONTENTS_HEADING = '## Table of contents';
    private const TABLE_OF_CONTENTS_MAX_LINE = 40;

    protected function setUp(): void
    {
        if (!SkillFile::exists()) {
            self::fail(SkillFile::missingFileMessage());
        }
    }

    public function testSkillFileIsShortEnoughToBeReadEntirely(): void
    {
        $lines = self::countLines(SkillFile::skill()->contents());

        self::assertLessThanOrEqual(self::MAX_SKILL_LINES, $lines, sprintf('%s has %d lines and the limit is %d. A line is one element of the file contents split on "\n", ignoring the final newline. Move the extra content to a file in %s/.', SkillFile::RELATIVE_PATH, $lines, self::MAX_SKILL_LINES, SkillFile::RELATIVE_REFERENCES_DIR));
    }

    public function testInstalledSkillBodyFitsTheContextBudgetOfAgents(): void
    {
        $skillInstaller = new SkillInstaller(sys_get_temp_dir(), SkillFile::repositoryRoot().'/skills', SkillFile::repositoryRoot().'/src/Resources/ai/guidelines.md', '0.0.0');
        $installedSkill = $skillInstaller->renderSkillFiles()['SKILL.md'];
        $body = preg_replace('/\A---\r?\n.*?\r?\n---\r?\n/s', '', $installedSkill);

        self::assertStringNotContainsString('<!--', $body, sprintf('The copy of %s written by "easyadmin:ai:install" still contains HTML comments. They only make sense in this repository, so the installer must remove all of them.', SkillFile::RELATIVE_PATH));
        self::assertLessThanOrEqual(self::MAX_INSTALLED_BODY_BYTES, \strlen($body), sprintf('The body of %s is %d bytes long once installed (without its HTML comments) and the limit is %d. Agents load the whole body every time they use the skill and some of them keep only its first 5,000 tokens in long sessions. Replace a rule instead of adding one, or move lists and tables to a file in %s/.', SkillFile::RELATIVE_PATH, \strlen($body), self::MAX_INSTALLED_BODY_BYTES, SkillFile::RELATIVE_REFERENCES_DIR));
    }

    public function testReferencesDirectoryOnlyContainsMarkdownFiles(): void
    {
        self::assertDirectoryExists(SkillFile::referencesDir(), sprintf('%s/ is missing. The skill keeps its long reference material there.', SkillFile::RELATIVE_REFERENCES_DIR));

        $unexpectedEntries = [];
        foreach (scandir(SkillFile::referencesDir()) ?: [] as $entry) {
            if ('.' === $entry || '..' === $entry) {
                continue;
            }

            $path = SkillFile::referencesDir().'/'.$entry;
            if (is_dir($path) || !str_ends_with($entry, '.md')) {
                $unexpectedEntries[] = $entry;
            }
        }

        self::assertSame([], $unexpectedEntries, sprintf('%s/ must contain Markdown files only and no subdirectories, because agents load references one level deep. Unexpected entries: %s.', SkillFile::RELATIVE_REFERENCES_DIR, implode(', ', $unexpectedEntries)));
    }

    public function testLongReferencesStartWithATableOfContents(): void
    {
        self::assertDirectoryExists(SkillFile::referencesDir(), sprintf('%s/ is missing. The skill keeps its long reference material there.', SkillFile::RELATIVE_REFERENCES_DIR));

        foreach (glob(SkillFile::referencesDir().'/*.md') ?: [] as $path) {
            $contents = file_get_contents($path);
            $lines = self::countLines($contents);
            if (self::MAX_REFERENCE_LINES_WITHOUT_CONTENTS >= $lines) {
                continue;
            }

            $firstLines = \array_slice(explode("\n", $contents), 0, self::TABLE_OF_CONTENTS_MAX_LINE);
            $headings = array_map(static fn (string $line): string => rtrim($line, "\r "), $firstLines);

            self::assertContains(self::TABLE_OF_CONTENTS_HEADING, $headings, sprintf('%s/%s has %d lines, so it needs a "%s" heading within its first %d lines. Agents read the table of contents to decide which part of a long reference to open.', SkillFile::RELATIVE_REFERENCES_DIR, basename($path), $lines, self::TABLE_OF_CONTENTS_HEADING, self::TABLE_OF_CONTENTS_MAX_LINE));
        }
    }

    private static function countLines(string $contents): int
    {
        if (str_ends_with($contents, "\n")) {
            $contents = substr($contents, 0, -1);
        }

        return \count(explode("\n", $contents));
    }
}
