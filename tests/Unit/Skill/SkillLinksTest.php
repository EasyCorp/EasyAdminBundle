<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Skill;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Skill\Support\SkillFile;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SkillLinksTest extends TestCase
{
    private const PACKAGE_PATH_PREFIX = 'vendor/easycorp/easyadmin-bundle/';
    private const DOC_PATH_PREFIX = 'doc/';
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

    #[DataProvider('provideSkillFiles')]
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

    #[DataProvider('provideSkillFiles')]
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

    #[DataProvider('provideSkillFiles')]
    public function testMentionedDocumentationPagesExistInTheRepository(string $path): void
    {
        $relativePath = self::relativePath($path);
        preg_match_all(self::BACKTICKED_SPAN_PATTERN, self::read($path), $spans);
        $missingPages = [];

        foreach ($spans[1] as $span) {
            if (!str_starts_with($span, self::DOC_PATH_PREFIX) || str_contains($span, '<') || str_contains($span, '*') || str_contains($span, ' ')) {
                continue;
            }

            if (!file_exists(SkillFile::repositoryRoot().'/'.rtrim($span, '/'))) {
                $missingPages[] = $span;
            }
        }

        self::assertSame([], $missingPages, sprintf('%s tells agents to read documentation pages that do not exist in this repository: %s. The skill mentions them relative to the package directory, so every "%s<page>" mention must resolve from the root of this repository.', $relativePath, implode(', ', $missingPages), self::DOC_PATH_PREFIX));
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
