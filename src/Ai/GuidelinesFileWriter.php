<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Ai;

/**
 * Adds, updates and reads the EasyAdmin block of the instructions files
 * (CLAUDE.md, AGENTS.md) read by AI coding agents.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final readonly class GuidelinesFileWriter
{
    public const START_MARKER = '<easyadmin-guidelines>';
    public const END_MARKER = '</easyadmin-guidelines>';

    public function hasBlock(string $path): bool
    {
        return null !== $this->readBlock($path);
    }

    public function readBlock(string $path): ?string
    {
        if (!is_file($path)) {
            return null;
        }

        $contents = $this->readFile($path);
        $startPosition = strpos($contents, self::START_MARKER);
        if (false === $startPosition) {
            return null;
        }

        $endPosition = strpos($contents, self::END_MARKER, $startPosition);
        if (false === $endPosition) {
            return null;
        }

        $blockPosition = $startPosition + \strlen(self::START_MARKER);

        return self::normalizeBlock(substr($contents, $blockPosition, $endPosition - $blockPosition));
    }

    public function validate(string $path): void
    {
        if (!is_file($path)) {
            return;
        }

        $contents = $this->readFile($path);
        $startPosition = strpos($contents, self::START_MARKER);
        $endPosition = strpos($contents, self::END_MARKER);
        $isBalanced = substr_count($contents, self::START_MARKER) === substr_count($contents, self::END_MARKER);

        if ($isBalanced && (false === $startPosition || (false !== $endPosition && $startPosition < $endPosition))) {
            return;
        }

        throw new \RuntimeException(sprintf('The "%s" file contains unbalanced "%s" and "%s" markers. Fix them manually and run this command again.', $path, self::START_MARKER, self::END_MARKER));
    }

    /**
     * Returns true when the contents of the file changed.
     */
    public function write(string $path, string $block): bool
    {
        $this->validate($path);

        $contents = is_file($path) ? $this->readFile($path) : '';
        $eol = str_contains($contents, "\r\n") ? "\r\n" : "\n";
        $blockContents = self::normalizeBlock($block);
        $fullBlock = '' === $blockContents
            ? self::START_MARKER.$eol.self::END_MARKER
            : self::START_MARKER.$eol.str_replace("\n", $eol, $blockContents).$eol.self::END_MARKER;

        $startPosition = strpos($contents, self::START_MARKER);
        if (false === $startPosition) {
            $existingContents = rtrim($contents, "\r\n");
            $newContents = '' === $existingContents ? $fullBlock : $existingContents.$eol.$eol.$fullBlock;
        } else {
            // validate() already checked that the closing marker exists after the opening one
            $endPosition = (int) strpos($contents, self::END_MARKER, $startPosition);
            $newContents = substr($contents, 0, $startPosition).$fullBlock.substr($contents, $endPosition + \strlen(self::END_MARKER));
        }

        $newContents = rtrim($newContents, "\r\n").$eol;

        if (is_file($path) && $newContents === $contents) {
            return false;
        }

        // LOCK_EX prevents mangled files when several processes run the command
        // at the same time (e.g. two concurrent "composer update" processes)
        if (false === file_put_contents($path, $newContents, \LOCK_EX)) {
            throw new \RuntimeException(sprintf('The "%s" file could not be written.', $path));
        }

        return true;
    }

    /**
     * Line endings are normalized to "\n" and blank lines are collapsed inside
     * the block, so the same block always produces the same bytes no matter how
     * many times it's written and read back.
     */
    public static function normalizeBlock(string $block): string
    {
        $normalizedBlock = trim(str_replace("\r\n", "\n", $block), "\n");

        return preg_replace("/\n{3,}/", "\n\n", $normalizedBlock) ?? $normalizedBlock;
    }

    private function readFile(string $path): string
    {
        $contents = file_get_contents($path);
        if (false === $contents) {
            throw new \RuntimeException(sprintf('The "%s" file could not be read.', $path));
        }

        return $contents;
    }
}
