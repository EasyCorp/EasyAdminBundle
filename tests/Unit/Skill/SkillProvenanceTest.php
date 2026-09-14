<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Skill;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Skill\Support\SkillFile;
use PHPUnit\Framework\TestCase;

class SkillProvenanceTest extends TestCase
{
    private const PROVENANCE_COMMENT_PATTERN = '/<!--\s*(.+?)\s*-->\s*$/';
    private const CODE_REFERENCE_PATTERN = '/^(?<path>[^:]+)::(?<symbol>[A-Za-z_][A-Za-z0-9_]*)$/';
    private const PROSE_REFERENCE_PATTERN = '/^(?<path>[^:]+):(?<start>\d+)(?:-(?<end>\d+))?$/';
    private const SOURCE_NAMESPACE_PREFIX = 'EasyCorp\Bundle\EasyAdminBundle\\';

    protected function setUp(): void
    {
        if (!SkillFile::exists()) {
            self::fail(SkillFile::missingFileMessage());
        }
    }

    public static function provideRules(): iterable
    {
        if (!SkillFile::exists()) {
            yield 'missing skill file' => [0, SkillFile::missingFileMessage()];

            return;
        }

        $rules = self::parseRules(SkillFile::skill()->contents());
        if ([] === $rules) {
            yield 'no rules found' => [0, sprintf('%s has no list items between "%s" and "%s" markers. Every rule an agent must follow belongs inside such a section.', SkillFile::RELATIVE_PATH, SkillFile::RULES_OPENING_MARKER, SkillFile::RULES_CLOSING_MARKER)];

            return;
        }

        foreach ($rules as $rule) {
            yield sprintf('%s:%d', SkillFile::RELATIVE_PATH, $rule['line']) => [$rule['line'], $rule['text']];
        }
    }

    public function testRuleMarkersAreBalanced(): void
    {
        $body = SkillFile::skill()->body();
        $openingMarkers = substr_count($body, SkillFile::RULES_OPENING_MARKER);
        $closingMarkers = substr_count($body, SkillFile::RULES_CLOSING_MARKER);

        self::assertSame($openingMarkers, $closingMarkers, sprintf('%s has %d "%s" markers and %d "%s" markers. Every rules section must be opened and closed.', SkillFile::RELATIVE_PATH, $openingMarkers, SkillFile::RULES_OPENING_MARKER, $closingMarkers, SkillFile::RULES_CLOSING_MARKER));
        self::assertGreaterThan(0, $openingMarkers, sprintf('%s has no rules section. Wrap every list of rules between "%s" and "%s", because that is where provenance comments are required.', SkillFile::RELATIVE_PATH, SkillFile::RULES_OPENING_MARKER, SkillFile::RULES_CLOSING_MARKER));
        self::assertCount($openingMarkers, SkillFile::skill()->ruleSections(), sprintf('The rules markers of %s are not in order. Each "%s" must be followed by its own "%s" before the next section opens.', SkillFile::RELATIVE_PATH, SkillFile::RULES_OPENING_MARKER, SkillFile::RULES_CLOSING_MARKER));
    }

    public function testEveryRuleSectionHasContent(): void
    {
        $sections = SkillFile::skill()->ruleSections();
        self::assertNotSame([], $sections, sprintf('%s has no rules section.', SkillFile::RELATIVE_PATH));

        foreach ($sections as $index => $section) {
            self::assertNotSame('', trim($section), sprintf('The rules section number %d of %s is empty. Remove the markers or add the rules they were meant to wrap.', $index + 1, SkillFile::RELATIVE_PATH));
        }
    }

    /**
     * @dataProvider provideRules
     */
    public function testEveryRuleCitesAnExistingSource(int $line, string $text): void
    {
        if (0 === $line) {
            self::fail($text);
        }

        $location = sprintf('%s:%d', SkillFile::RELATIVE_PATH, $line);
        $lastLine = self::lastLineOf($text);

        self::assertMatchesRegularExpression(self::PROVENANCE_COMMENT_PATTERN, $lastLine, sprintf('The rule at %s does not end with a provenance comment. Finish its last line with the source it comes from, such as "<!-- src/Config/Actions.php::setPermission -->" for code or "<!-- doc/security.rst:99 -->" for prose. A rule without provenance is not published.', $location));

        preg_match(self::PROVENANCE_COMMENT_PATTERN, $lastLine, $matches);
        $references = array_values(array_filter(array_map('trim', explode(';', $matches[1])), static fn (string $reference): bool => '' !== $reference));

        self::assertNotSame([], $references, sprintf('The provenance comment of the rule at %s is empty. Cite at least one file that proves the rule.', $location));

        foreach ($references as $reference) {
            self::assertReferenceResolves($reference, $location);
        }
    }

    /**
     * @return list<array{line: int, text: string}>
     */
    private static function parseRules(string $contents): array
    {
        $rules = [];
        $currentRule = null;
        $insideRulesSection = false;

        foreach (explode("\n", $contents) as $index => $rawLine) {
            $line = rtrim($rawLine, "\r");
            $trimmedLine = trim($line);

            if (SkillFile::RULES_OPENING_MARKER === $trimmedLine) {
                $insideRulesSection = true;
                $currentRule = null;
                continue;
            }

            if (SkillFile::RULES_CLOSING_MARKER === $trimmedLine) {
                if (null !== $currentRule) {
                    $rules[] = $currentRule;
                    $currentRule = null;
                }

                $insideRulesSection = false;
                continue;
            }

            if (!$insideRulesSection) {
                continue;
            }

            if (str_starts_with($line, '- ')) {
                if (null !== $currentRule) {
                    $rules[] = $currentRule;
                }

                $currentRule = ['line' => $index + 1, 'text' => $line];
                continue;
            }

            if (null === $currentRule) {
                continue;
            }

            if ('' === $trimmedLine || !str_starts_with($line, '  ')) {
                $rules[] = $currentRule;
                $currentRule = null;
                continue;
            }

            $currentRule['text'] .= "\n".$line;
        }

        if (null !== $currentRule) {
            $rules[] = $currentRule;
        }

        return $rules;
    }

    private static function lastLineOf(string $text): string
    {
        $lines = array_filter(array_map(static fn (string $line): string => rtrim($line), explode("\n", $text)), static fn (string $line): bool => '' !== $line);

        return (string) end($lines);
    }

    private static function assertReferenceResolves(string $reference, string $location): void
    {
        if (preg_match(self::CODE_REFERENCE_PATTERN, $reference, $matches)) {
            self::assertReferencedFileExists($matches['path'], $reference, $location);
            self::assertSymbolIsDeclared($matches['path'], $matches['symbol'], $reference, $location);

            return;
        }

        if (preg_match(self::PROSE_REFERENCE_PATTERN, $reference, $matches)) {
            self::assertReferencedFileExists($matches['path'], $reference, $location);
            self::assertLineNumbersExist($matches['path'], (int) $matches['start'], (int) ($matches['end'] ?? 0), $reference, $location);

            return;
        }

        if (!str_contains($reference, ':')) {
            self::assertReferencedFileExists($reference, $reference, $location);

            return;
        }

        self::fail(sprintf('The rule at %s cites "%s", which is not a valid provenance reference. Use "path/to/file.php::symbolName" for code, "path/to/file.rst:99" or "path/to/file.rst:99-120" for prose, or a bare "path/to/file" for a whole file, and separate several references with "; ".', $location, $reference));
    }

    private static function assertReferencedFileExists(string $path, string $reference, string $location): void
    {
        self::assertFileExists(\dirname(__DIR__, 3).'/'.$path, sprintf('The rule at %s cites "%s", but "%s" does not exist in this repository. Provenance paths are relative to the package root, so they work both here and in vendor/.', $location, $reference, $path));
    }

    private static function assertSymbolIsDeclared(string $path, string $symbol, string $reference, string $location): void
    {
        if (!str_starts_with($path, 'src/') || !str_ends_with($path, '.php')) {
            return;
        }

        $fqcn = self::SOURCE_NAMESPACE_PREFIX.str_replace('/', '\\', substr($path, 4, -4));

        self::assertTrue(class_exists($fqcn) || interface_exists($fqcn) || trait_exists($fqcn), sprintf('The rule at %s cites "%s", and "%s" does not autoload as a class, interface or trait. Check the path or the PSR-4 name.', $location, $reference, $fqcn));

        $reflection = new \ReflectionClass($fqcn);
        $isDeclared = $reflection->hasMethod($symbol) || $reflection->hasConstant($symbol) || $reflection->hasProperty($symbol);

        self::assertTrue($isDeclared, sprintf('The rule at %s cites "%s", but "%s" has no method, constant or property named "%s". The rule describes an API that no longer exists.', $location, $reference, $fqcn, $symbol));
    }

    private static function assertLineNumbersExist(string $path, int $start, int $end, string $reference, string $location): void
    {
        $contents = file_get_contents(\dirname(__DIR__, 3).'/'.$path);
        $totalLines = \count(explode("\n", str_ends_with($contents, "\n") ? substr($contents, 0, -1) : $contents));

        self::assertGreaterThan(0, $start, sprintf('The rule at %s cites "%s", and line numbers start at 1.', $location, $reference));
        self::assertLessThanOrEqual($totalLines, $start, sprintf('The rule at %s cites "%s", but "%s" only has %d lines. The cited text has moved, so check that the rule is still true.', $location, $reference, $path, $totalLines));

        if (0 === $end) {
            return;
        }

        self::assertGreaterThanOrEqual($start, $end, sprintf('The rule at %s cites the inverted line range "%s".', $location, $reference));
        self::assertLessThanOrEqual($totalLines, $end, sprintf('The rule at %s cites "%s", but "%s" only has %d lines. The cited text has moved, so check that the rule is still true.', $location, $reference, $path, $totalLines));
    }
}
