<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Skill;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Skill\Support\SkillFile;
use PHPUnit\Framework\TestCase;

class SkillTrailerTest extends TestCase
{
    private const ALLOWED_KEYS = ['must_exist', 'must_not_exist', 'constant', 'doc_file', 'source_contains', 'source_not_contains'];
    private const REQUIRED_KEYS = ['must_exist', 'must_not_exist', 'source_contains'];
    private const SYMBOL_PATTERN = '/^[A-Za-z_][A-Za-z0-9_]*(\\\\[A-Za-z_][A-Za-z0-9_]*)*(::[A-Za-z_][A-Za-z0-9_]*)?$/';
    private const SOURCE_SEPARATOR = ' | ';

    protected function setUp(): void
    {
        if (!SkillFile::exists()) {
            self::fail(SkillFile::missingFileMessage());
        }
    }

    public static function provideTrailerEntries(): iterable
    {
        if (!SkillFile::exists()) {
            yield 'missing skill file' => ['', SkillFile::missingFileMessage()];

            return;
        }

        $skill = SkillFile::skill();
        if (!$skill->hasTrailer()) {
            yield 'missing trailer' => ['', self::missingTrailerMessage()];

            return;
        }

        try {
            $entries = $skill->trailerEntries();
        } catch (\RuntimeException $exception) {
            yield 'malformed trailer' => ['', $exception->getMessage()];

            return;
        }

        if ([] === $entries) {
            yield 'empty trailer' => ['', sprintf('The "%s" block of %s has no entries. Add at least one entry per mandatory key: %s.', SkillFile::TRAILER_OPENING_MARKER, SkillFile::RELATIVE_PATH, implode(', ', self::REQUIRED_KEYS))];

            return;
        }

        foreach ($entries as [$key, $value]) {
            yield $key.': '.$value => [$key, $value];
        }
    }

    public function testSkillEndsWithAMachineCheckableTrailer(): void
    {
        self::assertTrue(SkillFile::skill()->hasTrailer(), self::missingTrailerMessage());
    }

    public function testEveryTrailerLineIsWellFormed(): void
    {
        $lines = $this->trailerLines();
        self::assertNotSame([], $lines, sprintf('The "%s" block of %s has no entries.', SkillFile::TRAILER_OPENING_MARKER, SkillFile::RELATIVE_PATH));

        foreach ($lines as $line) {
            self::assertMatchesRegularExpression('/^[a-z_]+:[ \t]+\S/', $line, sprintf('The trailer line "%s" is malformed. Write every entry as "key: value" on a single line.', $line));
        }
    }

    public function testEveryTrailerKeyIsSupported(): void
    {
        $entries = $this->trailerEntries();
        self::assertNotSame([], $entries, sprintf('The "%s" block of %s has no entries.', SkillFile::TRAILER_OPENING_MARKER, SkillFile::RELATIVE_PATH));

        foreach ($entries as [$key, $value]) {
            self::assertContains($key, self::ALLOWED_KEYS, sprintf('The trailer line "%s: %s" uses the unsupported key "%s". Supported keys: %s.', $key, $value, $key, implode(', ', self::ALLOWED_KEYS)));
        }
    }

    public function testTrailerCoversTheThreeMandatoryKindsOfCheck(): void
    {
        $keys = array_column($this->trailerEntries(), 0);

        foreach (self::REQUIRED_KEYS as $requiredKey) {
            self::assertContains($requiredKey, $keys, sprintf('The trailer of %s needs at least one "%s" entry. Without the three of them, an API that disappears, an API that comes back and a behavior described in the skill can all change unnoticed.', SkillFile::RELATIVE_PATH, $requiredKey));
        }
    }

    /**
     * @dataProvider provideTrailerEntries
     */
    public function testTrailerEntryStillHoldsForThisVersionOfTheBundle(string $key, string $value): void
    {
        if ('' === $key) {
            self::fail($value);
        }

        match ($key) {
            'must_exist' => $this->assertMustExist($value),
            'must_not_exist' => $this->assertMustNotExist($value),
            'constant' => $this->assertConstant($value),
            'doc_file' => $this->assertDocFile($value),
            'source_contains' => $this->assertSource($key, $value, true),
            'source_not_contains' => $this->assertSource($key, $value, false),
            default => self::fail(sprintf('The trailer line "%s: %s" uses the unsupported key "%s". Supported keys: %s.', $key, $value, $key, implode(', ', self::ALLOWED_KEYS))),
        };
    }

    /**
     * @return list<string>
     */
    private function trailerLines(): array
    {
        $skill = SkillFile::skill();
        if (!$skill->hasTrailer()) {
            self::fail(self::missingTrailerMessage());
        }

        return $skill->trailerLines();
    }

    /**
     * @return list<array{string, string}>
     */
    private function trailerEntries(): array
    {
        $skill = SkillFile::skill();
        if (!$skill->hasTrailer()) {
            self::fail(self::missingTrailerMessage());
        }

        return $skill->trailerEntries();
    }

    private static function missingTrailerMessage(): string
    {
        return sprintf('%s has no machine-checkable trailer. Add a block that starts with a "%s" line and ends with a "%s" line, so CI fails when the skill stops matching the code.', SkillFile::RELATIVE_PATH, SkillFile::TRAILER_OPENING_MARKER, SkillFile::TRAILER_CLOSING_MARKER);
    }

    private static function classLikeExists(string $fqcn): bool
    {
        return class_exists($fqcn) || interface_exists($fqcn) || trait_exists($fqcn);
    }

    private function assertMustExist(string $value): void
    {
        [$fqcn, $member] = $this->splitSymbol('must_exist', $value);

        self::assertTrue(self::classLikeExists($fqcn), sprintf('The trailer entry "must_exist: %s" refers to "%s", and this version of the bundle has no such class, interface or trait. The skill teaches an API that does not exist.', $value, $fqcn));

        if (null === $member) {
            return;
        }

        self::assertTrue((new \ReflectionClass($fqcn))->hasMethod($member), sprintf('The trailer entry "must_exist: %s" refers to a method that "%s" no longer declares or inherits. Update the rules that rely on it before removing the entry.', $value, $fqcn));
    }

    private function assertMustNotExist(string $value): void
    {
        [$fqcn, $member] = $this->splitSymbol('must_not_exist', $value);
        $exists = self::classLikeExists($fqcn) && (null === $member || (new \ReflectionClass($fqcn))->hasMethod($member));

        self::assertFalse($exists, sprintf('The trailer entry "must_not_exist: %s" describes something that does exist in this version of the bundle. The skill tells agents to avoid an API that is now valid, so update the rule and remove the entry.', $value));
    }

    private function assertConstant(string $value): void
    {
        self::assertStringContainsString(' = ', $value, sprintf('The trailer entry "constant: %s" is malformed. Write it as "Fqcn::CONSTANT_NAME = expected value".', $value));

        [$symbol, $expectedValue] = explode(' = ', $value, 2);
        [$fqcn, $name] = $this->splitSymbol('constant', $symbol);

        self::assertNotNull($name, sprintf('The trailer entry "constant: %s" is malformed. Write it as "Fqcn::CONSTANT_NAME = expected value".', $value));
        self::assertTrue(self::classLikeExists($fqcn), sprintf('The trailer entry "constant: %s" refers to "%s", and this version of the bundle has no such class, interface or trait.', $value, $fqcn));

        $reflection = new \ReflectionClass($fqcn);
        self::assertTrue($reflection->hasConstant($name), sprintf('The trailer entry "constant: %s" refers to a constant that "%s" does not declare.', $value, $fqcn));

        $actualValue = $reflection->getConstant($name);
        self::assertTrue(null === $actualValue || \is_scalar($actualValue), sprintf('The trailer entry "constant: %s" refers to a constant that is not a scalar value, so it cannot be compared as text.', $value));
        self::assertSame($expectedValue, (string) $actualValue, sprintf('The trailer entry "constant: %s" expects "%s::%s" to be "%s", but it is "%s". Every rule that repeats that value is now wrong.', $value, $fqcn, $name, $expectedValue, (string) $actualValue));
    }

    private function assertDocFile(string $value): void
    {
        self::assertFileExists(\dirname(__DIR__, 3).'/'.$value, sprintf('The trailer entry "doc_file: %s" points to a documentation page that does not exist. The skill sends agents to that page, so fix the path or the entry.', $value));
    }

    private function assertSource(string $key, string $value, bool $mustContain): void
    {
        self::assertStringContainsString(self::SOURCE_SEPARATOR, $value, sprintf('The trailer entry "%s: %s" is malformed. Write it as "path/to/file.php%sliteral text", split on the first "%s".', $key, $value, self::SOURCE_SEPARATOR, self::SOURCE_SEPARATOR));

        [$relativePath, $literal] = explode(self::SOURCE_SEPARATOR, $value, 2);
        $path = \dirname(__DIR__, 3).'/'.$relativePath;

        self::assertFileExists($path, sprintf('The trailer entry "%s: %s" refers to "%s", which does not exist in this repository.', $key, $value, $relativePath));

        $contents = file_get_contents($path);

        if ($mustContain) {
            self::assertStringContainsString($literal, $contents, sprintf('The trailer entry "%s: %s" no longer holds: "%s" does not contain that text any more. The skill describes a behavior that the code has changed.', $key, $value, $relativePath));

            return;
        }

        self::assertStringNotContainsString($literal, $contents, sprintf('The trailer entry "%s: %s" no longer holds: "%s" now contains that text. The skill describes a behavior that the code has changed.', $key, $value, $relativePath));
    }

    /**
     * @return array{string, string|null}
     */
    private function splitSymbol(string $key, string $value): array
    {
        self::assertMatchesRegularExpression(self::SYMBOL_PATTERN, $value, sprintf('The trailer entry "%s: %s" is malformed. Write it as a fully qualified class name, optionally followed by "::" and a method name.', $key, $value));

        $parts = explode('::', $value);

        return [$parts[0], $parts[1] ?? null];
    }
}
