<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Ai;

use EasyCorp\Bundle\EasyAdminBundle\Ai\GuidelinesFileWriter;

class GuidelinesFileWriterTest extends AiTestCase
{
    private const BLOCK = "Use EasyAdmin 5.5.2.\n\nRead the skill at `.claude/skills/easyadmin/SKILL.md`.";

    private GuidelinesFileWriter $guidelinesFileWriter;
    private string $filePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->guidelinesFileWriter = new GuidelinesFileWriter();
        $this->filePath = $this->projectDir.'/CLAUDE.md';
    }

    public function testWriteCreatesTheFileWhenItDoesNotExist(): void
    {
        $this->assertFalse($this->guidelinesFileWriter->hasBlock($this->filePath));
        $this->assertNull($this->guidelinesFileWriter->readBlock($this->filePath));

        $this->assertTrue($this->guidelinesFileWriter->write($this->filePath, self::BLOCK));

        $this->assertStringEqualsFile($this->filePath, $this->wrap(self::BLOCK)."\n");
        $this->assertTrue($this->guidelinesFileWriter->hasBlock($this->filePath));
        $this->assertSame(self::BLOCK, $this->guidelinesFileWriter->readBlock($this->filePath));
    }

    public function testWriteInAnEmptyFile(): void
    {
        $this->filesystem->dumpFile($this->filePath, '');

        $this->guidelinesFileWriter->write($this->filePath, self::BLOCK);

        $this->assertStringEqualsFile($this->filePath, $this->wrap(self::BLOCK)."\n");
    }

    public function testWriteAppendsTheBlockToAnExistingFile(): void
    {
        $this->filesystem->dumpFile($this->filePath, "# Project instructions\n\nBe nice.\n");

        $this->guidelinesFileWriter->write($this->filePath, self::BLOCK);

        $this->assertStringEqualsFile($this->filePath, "# Project instructions\n\nBe nice.\n\n".$this->wrap(self::BLOCK)."\n");
    }

    public function testWriteInAFileWithoutATrailingNewLine(): void
    {
        $this->filesystem->dumpFile($this->filePath, '# Project instructions');

        $this->guidelinesFileWriter->write($this->filePath, self::BLOCK);

        $this->assertStringEqualsFile($this->filePath, "# Project instructions\n\n".$this->wrap(self::BLOCK)."\n");
    }

    public function testWriteEnsuresExactlyOneTrailingNewLine(): void
    {
        $this->filesystem->dumpFile($this->filePath, "# Project instructions\n\n\n\n");

        $this->guidelinesFileWriter->write($this->filePath, self::BLOCK);

        $this->assertStringEqualsFile($this->filePath, "# Project instructions\n\n".$this->wrap(self::BLOCK)."\n");
    }

    public function testWriteKeepsTheWindowsLineEndingsOfTheFile(): void
    {
        $this->filesystem->dumpFile($this->filePath, "# Project instructions\r\n\r\nBe nice.\r\n");

        $this->guidelinesFileWriter->write($this->filePath, self::BLOCK);

        $expectedBlock = str_replace("\n", "\r\n", $this->wrap(self::BLOCK));
        $this->assertStringEqualsFile($this->filePath, "# Project instructions\r\n\r\nBe nice.\r\n\r\n".$expectedBlock."\r\n");
        $this->assertSame(self::BLOCK, $this->guidelinesFileWriter->readBlock($this->filePath));
    }

    public function testWriteKeepsTheByteOrderMarkAndTheTrailingWhitespaceOfTheFile(): void
    {
        $originalContents = "\u{FEFF}# Project instructions   \n\nBe nice.\t\n";
        $this->filesystem->dumpFile($this->filePath, $originalContents);

        $this->guidelinesFileWriter->write($this->filePath, self::BLOCK);

        $this->assertStringEqualsFile($this->filePath, $originalContents."\n".$this->wrap(self::BLOCK)."\n");
    }

    public function testWriteReplacesTheExistingBlockAndKeepsEverythingElseUntouched(): void
    {
        $this->filesystem->dumpFile($this->filePath, "# Project instructions\n\n\n\n".$this->wrap('Some outdated contents.')."\n\n\n\nBe nice.\n");

        $this->guidelinesFileWriter->write($this->filePath, self::BLOCK);

        $this->assertStringEqualsFile($this->filePath, "# Project instructions\n\n\n\n".$this->wrap(self::BLOCK)."\n\n\n\nBe nice.\n");
    }

    public function testWriteOnlyReplacesTheFirstBlock(): void
    {
        $secondBlock = $this->wrap('The second block is never replaced.');
        $this->filesystem->dumpFile($this->filePath, $this->wrap('Some outdated contents.')."\n\n".$secondBlock."\n");

        $this->guidelinesFileWriter->write($this->filePath, self::BLOCK);

        $this->assertStringEqualsFile($this->filePath, $this->wrap(self::BLOCK)."\n\n".$secondBlock."\n");
    }

    public function testWriteCollapsesTheBlankLinesInsideTheBlockOnly(): void
    {
        $this->filesystem->dumpFile($this->filePath, "# Project instructions\n\n\n\n");

        $this->guidelinesFileWriter->write($this->filePath, "First line.\n\n\n\n\nLast line.");

        $this->assertStringEqualsFile($this->filePath, "# Project instructions\n\n".$this->wrap("First line.\n\nLast line.")."\n");
    }

    public function testWriteReturnsFalseWhenTheFileDoesNotChange(): void
    {
        $this->assertTrue($this->guidelinesFileWriter->write($this->filePath, self::BLOCK));
        $contentsAfterFirstWrite = file_get_contents($this->filePath);

        $this->assertFalse($this->guidelinesFileWriter->write($this->filePath, self::BLOCK));
        $this->assertStringEqualsFile($this->filePath, $contentsAfterFirstWrite);
    }

    /**
     * @dataProvider provideFilesWithUnbalancedMarkers
     */
    public function testUnbalancedMarkers(string $contents): void
    {
        $this->filesystem->dumpFile($this->filePath, $contents);

        try {
            $this->guidelinesFileWriter->write($this->filePath, self::BLOCK);
            $this->fail('The unbalanced markers of the file did not throw an exception.');
        } catch (\RuntimeException $e) {
            $this->assertSame(sprintf('The "%s" file contains unbalanced "<easyadmin-guidelines>" and "</easyadmin-guidelines>" markers. Fix them manually and run this command again.', $this->filePath), $e->getMessage());
        }

        $this->assertStringEqualsFile($this->filePath, $contents);
    }

    public static function provideFilesWithUnbalancedMarkers(): \Generator
    {
        yield 'opening marker only' => ["<easyadmin-guidelines>\nSome contents.\n"];
        yield 'closing marker only' => ["Some contents.\n</easyadmin-guidelines>\n"];
        yield 'markers in the wrong order' => ["</easyadmin-guidelines>\nSome contents.\n<easyadmin-guidelines>\n"];
        yield 'two opening markers' => ["<easyadmin-guidelines>\n<easyadmin-guidelines>\nSome contents.\n</easyadmin-guidelines>\n"];
    }

    public function testValidateIgnoresFilesThatDoNotExist(): void
    {
        $this->guidelinesFileWriter->validate($this->filePath);

        $this->assertFileDoesNotExist($this->filePath);
    }

    public function testReadBlockIgnoresAnUnbalancedBlock(): void
    {
        $this->filesystem->dumpFile($this->filePath, "<easyadmin-guidelines>\nSome contents.\n");

        $this->assertFalse($this->guidelinesFileWriter->hasBlock($this->filePath));
        $this->assertNull($this->guidelinesFileWriter->readBlock($this->filePath));
    }

    private function wrap(string $block): string
    {
        return GuidelinesFileWriter::START_MARKER."\n".$block."\n".GuidelinesFileWriter::END_MARKER;
    }
}
