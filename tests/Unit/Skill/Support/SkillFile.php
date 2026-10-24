<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Skill\Support;

/**
 * Minimal reader for the agent skill shipped in skills/easyadmin/.
 *
 * This repository doesn't install symfony/yaml, so the front matter is read by
 * hand. The reader understands only the constructs the skill files are allowed
 * to use and throws on anything else instead of guessing a value.
 */
final class SkillFile
{
    public const RELATIVE_DIR = 'skills/easyadmin';
    public const RELATIVE_PATH = 'skills/easyadmin/SKILL.md';
    public const RELATIVE_REFERENCES_DIR = 'skills/easyadmin/references';

    public const STYLE_PLAIN = 'plain';
    public const STYLE_FOLDED = 'folded';
    public const STYLE_DOUBLE_QUOTED = 'double-quoted';
    public const STYLE_SINGLE_QUOTED = 'single-quoted';

    public const TRAILER_OPENING_MARKER = '<!-- skill-check';
    public const TRAILER_CLOSING_MARKER = '-->';
    public const RULES_OPENING_MARKER = '<!-- rules:start -->';
    public const RULES_CLOSING_MARKER = '<!-- rules:end -->';

    /**
     * @var array<string, string|array<string, string>>|null
     */
    private ?array $values = null;

    /**
     * @var array<string, string>|null
     */
    private ?array $styles = null;

    private function __construct(
        private readonly string $path,
        private readonly string $contents,
    ) {
    }

    public static function repositoryRoot(): string
    {
        return \dirname(__DIR__, 4);
    }

    public static function skillPath(): string
    {
        return self::repositoryRoot().'/'.self::RELATIVE_PATH;
    }

    public static function skillDir(): string
    {
        return self::repositoryRoot().'/'.self::RELATIVE_DIR;
    }

    public static function referencesDir(): string
    {
        return self::repositoryRoot().'/'.self::RELATIVE_REFERENCES_DIR;
    }

    public static function exists(): bool
    {
        return is_file(self::skillPath());
    }

    public static function missingFileMessage(): string
    {
        return sprintf('%s is missing. The agent skill must ship inside the Composer package, so create that file before running the skill maintenance tests.', self::RELATIVE_PATH);
    }

    public static function skill(): self
    {
        return self::fromPath(self::skillPath());
    }

    public static function fromPath(string $path): self
    {
        if (!is_file($path)) {
            throw new \RuntimeException(sprintf('The file "%s" does not exist.', $path));
        }

        $contents = file_get_contents($path);
        if (false === $contents) {
            throw new \RuntimeException(sprintf('The file "%s" cannot be read.', $path));
        }

        return new self($path, $contents);
    }

    public function path(): string
    {
        return $this->path;
    }

    public function contents(): string
    {
        return $this->contents;
    }

    /**
     * @return list<string>
     */
    public function frontmatterLines(): array
    {
        $lines = explode("\n", $this->contents);

        return \array_slice($lines, 1, $this->frontmatterClosingIndex() - 1);
    }

    /**
     * @return array<string, string|array<string, string>>
     */
    public function frontmatter(): array
    {
        $this->parseFrontmatter();

        return $this->values;
    }

    /**
     * @return array<string, string>
     */
    public function metadata(): array
    {
        $metadata = $this->frontmatter()['metadata'] ?? [];
        if (!\is_array($metadata)) {
            throw new \RuntimeException(sprintf('The "metadata" front matter key of "%s" must be a mapping of keys and values, but it holds the scalar value "%s".', $this->path, $metadata));
        }

        return $metadata;
    }

    public function descriptionStyle(): string
    {
        $this->parseFrontmatter();

        if (!isset($this->styles['description'])) {
            if (\array_key_exists('description', $this->values)) {
                throw new \RuntimeException(sprintf('The front matter of "%s" defines "description" as a nested mapping or as an empty value instead of a text value.', $this->path));
            }

            throw new \RuntimeException(sprintf('The front matter of "%s" has no "description" key, so its value style cannot be read.', $this->path));
        }

        return $this->styles['description'];
    }

    public function body(): string
    {
        $lines = explode("\n", $this->contents);

        return implode("\n", \array_slice($lines, $this->frontmatterClosingIndex() + 1));
    }

    public function hasTrailer(): bool
    {
        return null !== $this->trailerOpeningIndex();
    }

    /**
     * @return list<string>
     */
    public function trailerLines(): array
    {
        $lines = explode("\n", $this->body());
        $start = $this->trailerOpeningIndex();
        if (null === $start) {
            throw new \RuntimeException(sprintf('The file "%s" has no machine-checkable trailer. Add a block that starts with a "%s" line and ends with a "%s" line.', $this->path, self::TRAILER_OPENING_MARKER, self::TRAILER_CLOSING_MARKER));
        }

        $entries = [];
        $total = \count($lines);
        for ($index = $start + 1; $index < $total; ++$index) {
            $line = trim($lines[$index]);
            if (self::TRAILER_CLOSING_MARKER === $line) {
                return $entries;
            }

            if ('' === $line) {
                continue;
            }

            $entries[] = $line;
        }

        throw new \RuntimeException(sprintf('The machine-checkable trailer of "%s" is never closed. Add a "%s" line after the last trailer entry.', $this->path, self::TRAILER_CLOSING_MARKER));
    }

    /**
     * @return list<array{string, string}>
     */
    public function trailerEntries(): array
    {
        $entries = [];
        foreach ($this->trailerLines() as $line) {
            if (!preg_match('/^([a-z_]+):[ \t]+(\S.*)$/', $line, $matches)) {
                throw new \RuntimeException(sprintf('The machine-checkable trailer of "%s" contains the malformed line "%s". Every trailer line must be written as "key: value".', $this->path, $line));
            }

            $entries[] = [$matches[1], rtrim($matches[2])];
        }

        return $entries;
    }

    /**
     * @return list<string>
     */
    public function ruleSections(): array
    {
        $pattern = sprintf('/%s(.*?)%s/s', preg_quote(self::RULES_OPENING_MARKER, '/'), preg_quote(self::RULES_CLOSING_MARKER, '/'));
        preg_match_all($pattern, $this->body(), $matches);

        return $matches[1];
    }

    private function frontmatterClosingIndex(): int
    {
        $lines = explode("\n", $this->contents);
        if ('---' !== rtrim($lines[0], "\r")) {
            throw new \RuntimeException(sprintf('The file "%s" must start with a "---" line that opens the YAML front matter.', $this->path));
        }

        $total = \count($lines);
        for ($index = 1; $index < $total; ++$index) {
            if ('---' === rtrim($lines[$index], "\r")) {
                return $index;
            }
        }

        throw new \RuntimeException(sprintf('The front matter of "%s" is never closed. Add a "---" line after the last front matter key.', $this->path));
    }

    private function trailerOpeningIndex(): ?int
    {
        foreach (explode("\n", $this->body()) as $index => $line) {
            if (self::TRAILER_OPENING_MARKER === trim($line)) {
                return $index;
            }
        }

        return null;
    }

    private function parseFrontmatter(): void
    {
        if (null !== $this->values) {
            return;
        }

        $lines = $this->frontmatterLines();
        $values = [];
        $styles = [];
        $total = \count($lines);
        $index = 0;

        while ($index < $total) {
            $line = rtrim($lines[$index], "\r");
            if ('' === trim($line)) {
                ++$index;
                continue;
            }

            if (preg_match('/^[ \t]/', $line)) {
                throw new \RuntimeException(sprintf('Cannot read the front matter of "%s": the indented line "%s" does not belong to any key.', $this->path, $line));
            }

            if (!preg_match('/^([A-Za-z0-9_-]+):[ \t]*(.*)$/', $line, $matches)) {
                throw new \RuntimeException(sprintf('Cannot read the front matter of "%s": the line "%s" is not a "key: value" pair. This reader supports top-level keys with scalar values, folded block scalars and one level of nested mapping.', $this->path, $line));
            }

            $key = $matches[1];
            $rawValue = rtrim($matches[2]);
            ++$index;

            if (\array_key_exists($key, $values)) {
                throw new \RuntimeException(sprintf('Cannot read the front matter of "%s": the key "%s" is defined more than once.', $this->path, $key));
            }

            if ('' === $rawValue) {
                $values[$key] = $this->readNestedMapping($lines, $index);
                continue;
            }

            if (preg_match('/^>[-+]?$/', $rawValue)) {
                $values[$key] = $this->readFoldedBlock($lines, $index, $key);
                $styles[$key] = self::STYLE_FOLDED;
                continue;
            }

            $scalar = $this->parseScalarValue($rawValue, $line);
            $styles[$key] = $scalar['style'];
            $values[$key] = self::STYLE_PLAIN === $scalar['style']
                ? $this->readPlainContinuation($lines, $index, $scalar['value'])
                : $scalar['value'];
        }

        $this->values = $values;
        $this->styles = $styles;
    }

    /**
     * @param list<string> $lines
     *
     * @return array<string, string>
     */
    private function readNestedMapping(array $lines, int &$index): array
    {
        $mapping = [];
        $total = \count($lines);

        while ($index < $total) {
            $line = rtrim($lines[$index], "\r");
            if ('' === trim($line)) {
                ++$index;
                continue;
            }

            if (!preg_match('/^[ \t]/', $line)) {
                break;
            }

            if (!preg_match('/^[ \t]+([A-Za-z0-9_-]+):[ \t]*(\S.*)$/', $line, $matches)) {
                throw new \RuntimeException(sprintf('Cannot read the front matter of "%s": the nested line "%s" is not a "key: value" pair with a scalar value. This reader supports only one level of nested mappings.', $this->path, $line));
            }

            $mapping[$matches[1]] = $this->parseScalarValue(rtrim($matches[2]), $line)['value'];
            ++$index;
        }

        return $mapping;
    }

    /**
     * @param list<string> $lines
     */
    private function readFoldedBlock(array $lines, int &$index, string $key): string
    {
        $parts = [];
        $total = \count($lines);

        while ($index < $total) {
            $line = rtrim($lines[$index], "\r");
            if ('' === trim($line)) {
                ++$index;
                continue;
            }

            if (!preg_match('/^[ \t]/', $line)) {
                break;
            }

            $parts[] = trim($line);
            ++$index;
        }

        if ([] === $parts) {
            throw new \RuntimeException(sprintf('Cannot read the front matter of "%s": the folded block scalar of the "%s" key has no indented lines below it.', $this->path, $key));
        }

        return implode(' ', $parts);
    }

    /**
     * @param list<string> $lines
     */
    private function readPlainContinuation(array $lines, int &$index, string $value): string
    {
        $total = \count($lines);

        while ($index < $total) {
            $line = rtrim($lines[$index], "\r");
            if ('' === trim($line) || !preg_match('/^[ \t]/', $line)) {
                break;
            }

            $value .= ' '.trim($line);
            ++$index;
        }

        return $value;
    }

    /**
     * @return array{value: string, style: string}
     */
    private function parseScalarValue(string $rawValue, string $line): array
    {
        if (preg_match('/^"(.*)"$/', $rawValue, $matches)) {
            return ['value' => $this->unescapeDoubleQuoted($matches[1]), 'style' => self::STYLE_DOUBLE_QUOTED];
        }

        if (preg_match("/^'(.*)'$/", $rawValue, $matches)) {
            return ['value' => str_replace("''", "'", $matches[1]), 'style' => self::STYLE_SINGLE_QUOTED];
        }

        if (str_starts_with($rawValue, '"') || str_starts_with($rawValue, "'")) {
            throw new \RuntimeException(sprintf('Cannot read the front matter of "%s": the quoted value in the line "%s" is not closed on the same line, and multi-line quoted scalars are not supported.', $this->path, $line));
        }

        if (preg_match('/^\|[-+]?$/', $rawValue)) {
            throw new \RuntimeException(sprintf('Cannot read the front matter of "%s": the literal block scalar in the line "%s" is not supported. Use a folded block scalar (">-") so the value is joined into a single line.', $this->path, $line));
        }

        if (preg_match('/^>[-+]?$/', $rawValue)) {
            throw new \RuntimeException(sprintf('Cannot read the front matter of "%s": the folded block scalar in the line "%s" is not supported here, because only top-level keys can use one.', $this->path, $line));
        }

        if (preg_match('/^[\[{&*!]/', $rawValue)) {
            throw new \RuntimeException(sprintf('Cannot read the front matter of "%s": the value in the line "%s" uses a flow collection, an anchor, an alias or a tag, and none of them is supported.', $this->path, $line));
        }

        return ['value' => $rawValue, 'style' => self::STYLE_PLAIN];
    }

    private function unescapeDoubleQuoted(string $value): string
    {
        return preg_replace_callback('/\\\\(.)/', function (array $matches): string {
            return match ($matches[1]) {
                'n' => "\n",
                't' => "\t",
                'r' => "\r",
                '"' => '"',
                '/' => '/',
                '\\' => '\\',
                default => throw new \RuntimeException(sprintf('Cannot read the front matter of "%s": the escape sequence "\%s" is not supported in double-quoted values.', $this->path, $matches[1])),
            };
        }, $value);
    }
}
