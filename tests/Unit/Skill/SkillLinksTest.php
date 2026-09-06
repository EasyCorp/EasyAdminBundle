<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Skill;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Skill\Support\SkillFile;
use PHPUnit\Framework\TestCase;

class SkillLinksTest extends TestCase
{
    private const PACKAGE_PATH_PREFIX = 'vendor/easycorp/easyadmin-bundle/';
    private const MARKDOWN_LINK_PATTERN = '/\[[^\]]*\]\(([^)\s]+)\)/';
    private const BACKTICKED_SPAN_PATTERN = '/`([^`\n]+)`/';

    protected function setUp(): void
    {
        if (!SkillFile::exists()) {
            self::fail(SkillFile::missingFileMessage());
        }
    }

    public static function provideSkillFiles(): iterable
    {
        yield SkillFile::RELATIVE_PATH => [SkillFile::skillPath()];

        foreach (glob(SkillFile::referencesDir().'/*.md') ?: [] as $path) {
            yield SkillFile::RELATIVE_REFERENCES_DIR.'/'.basename($path) => [$path];
        }
    }

    /**
     * @dataProvider provideSkillFiles
     */
    public function testRelativeLinksPointToExistingFiles(string $path): void
    {
        $relativePath = self::relativePath($path);
        preg_match_all(self::MARKDOWN_LINK_PATTERN, self::read($path), $matches);
        $brokenLinks = [];

        foreach ($matches[1] as $target) {
            if (self::isExternalTarget($target)) {
                continue;
            }

            $target = explode('#', $target)[0];
            if ('' === $target) {
                continue;
            }

            if (!file_exists(\dirname($path).'/'.$target)) {
                $brokenLinks[] = $target;
            }
        }

        self::assertSame([], $brokenLinks, sprintf('%s links to files that do not exist: %s. Link targets are resolved from the directory of the file that contains them, so fix the targets or add the missing files.', $relativePath, implode(', ', $brokenLinks)));
    }

    /**
     * @dataProvider provideSkillFiles
     */
    public function testMentionedPackagePathsExistInTheRepository(string $path): void
    {
        $relativePath = self::relativePath($path);
        preg_match_all(self::BACKTICKED_SPAN_PATTERN, self::read($path), $spans);
        $missingPaths = [];

        foreach ($spans[1] as $span) {
            preg_match_all('#'.preg_quote(self::PACKAGE_PATH_PREFIX, '#').'([^\s`\'"]+)#', $span, $mentions);

            foreach ($mentions[1] as $mention) {
                $mention = rtrim($mention, ',;');
                if (str_contains($mention, '<') || str_contains($mention, '*')) {
                    continue;
                }

                if (!file_exists(SkillFile::repositoryRoot().'/'.rtrim($mention, '/'))) {
                    $missingPaths[] = self::PACKAGE_PATH_PREFIX.$mention;
                }
            }
        }

        self::assertSame([], $missingPaths, sprintf('%s tells agents to open files of the installed package that do not exist in this repository: %s. Every "%s<path>" mention must resolve to "<path>" here, because both copies ship the same files.', $relativePath, implode(', ', $missingPaths), self::PACKAGE_PATH_PREFIX));
    }

    private static function isExternalTarget(string $target): bool
    {
        return (bool) preg_match('#^(https?:|mailto:|ftp:|//|/)#i', $target);
    }

    private static function relativePath(string $path): string
    {
        return ltrim(str_replace(SkillFile::repositoryRoot(), '', $path), '/');
    }

    private static function read(string $path): string
    {
        $contents = file_get_contents($path);
        if (false === $contents) {
            self::fail(sprintf('The file "%s" cannot be read.', $path));
        }

        return $contents;
    }
}
