<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Ai;

use EasyCorp\Bundle\EasyAdminBundle\Ai\AgentTarget;
use EasyCorp\Bundle\EasyAdminBundle\Ai\GuidelinesFileWriter;
use EasyCorp\Bundle\EasyAdminBundle\Ai\SkillInstaller;
use EasyCorp\Bundle\EasyAdminBundle\Command\UpdateAiSkillCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class UpdateAiSkillCommandTest extends AiTestCase
{
    /**
     * @var string|false
     */
    private $originalComposerDevMode;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalComposerDevMode = getenv('COMPOSER_DEV_MODE');
    }

    protected function tearDown(): void
    {
        putenv(false === $this->originalComposerDevMode ? 'COMPOSER_DEV_MODE' : 'COMPOSER_DEV_MODE='.$this->originalComposerDevMode);

        parent::tearDown();
    }

    public function testUpdateWhenNothingIsInstalled(): void
    {
        $commandTester = $this->createCommandTester();

        $exitCode = $commandTester->execute([], ['interactive' => false]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertSame('', $commandTester->getDisplay());
        $this->assertSame([], $this->snapshotOf($this->projectDir));
    }

    public function testUpdateWhenTheSkillIsMissingFromThePackageAndNothingIsInstalled(): void
    {
        $this->filesystem->remove($this->skillsSourceDir());
        $commandTester = $this->createCommandTester();

        $exitCode = $commandTester->execute([], ['interactive' => false]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertSame('', $commandTester->getDisplay());
    }

    public function testUpdateWhenTheSkillIsMissingFromThePackage(): void
    {
        $this->installSkillFor(AgentTarget::ClaudeCode);
        $this->filesystem->remove($this->skillsSourceDir());
        $commandTester = $this->createCommandTester();

        $exitCode = $commandTester->execute([], ['interactive' => false]);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('The AI agent skill files are missing from the EasyAdmin package', $commandTester->getDisplay());
    }

    public function testUpdateDoesNothingWhenComposerDevModeIsDisabled(): void
    {
        putenv('COMPOSER_DEV_MODE=0');
        $this->installSkillFor(AgentTarget::ClaudeCode);
        $this->makeInstalledSkillOutdated(AgentTarget::ClaudeCode);
        $projectFilesBeforeUpdating = $this->snapshotOf($this->projectDir);
        $commandTester = $this->createCommandTester();

        $exitCode = $commandTester->execute([], ['interactive' => false]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertSame('', $commandTester->getDisplay());
        $this->assertSame($projectFilesBeforeUpdating, $this->snapshotOf($this->projectDir));
    }

    public function testUpdateRefreshesAnOutdatedSkill(): void
    {
        $this->installSkillFor(AgentTarget::ClaudeCode);
        $expectedProjectFiles = $this->snapshotOf($this->projectDir);
        $this->makeInstalledSkillOutdated(AgentTarget::ClaudeCode);
        $commandTester = $this->createCommandTester();

        $exitCode = $commandTester->execute([], ['interactive' => false]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertSame('Updated the EasyAdmin skill in ".claude/skills/easyadmin".', trim($commandTester->getDisplay()));
        $this->assertSame($expectedProjectFiles, $this->snapshotOf($this->projectDir));
    }

    public function testUpdateWhenEverythingIsUpToDate(): void
    {
        $this->installSkillFor(AgentTarget::ClaudeCode);
        $expectedProjectFiles = $this->snapshotOf($this->projectDir);
        $commandTester = $this->createCommandTester();

        $exitCode = $commandTester->execute([], ['interactive' => false]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertSame('', $commandTester->getDisplay());
        $this->assertSame($expectedProjectFiles, $this->snapshotOf($this->projectDir));
    }

    public function testUpdateRefreshesAnOutdatedGuidelinesBlock(): void
    {
        $this->installSkillFor(AgentTarget::ClaudeCode);
        $guidelinesFilePath = $this->projectDir.'/CLAUDE.md';
        (new GuidelinesFileWriter())->write($guidelinesFilePath, 'Some outdated contents.');
        $commandTester = $this->createCommandTester();

        $exitCode = $commandTester->execute([], ['interactive' => false]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertSame('Updated the EasyAdmin section in "CLAUDE.md".', trim($commandTester->getDisplay()));
        $this->assertStringContainsString('This project uses EasyAdmin 5.5.2-DEV.', file_get_contents($guidelinesFilePath));
    }

    public function testUpdateNeverCreatesTheInstructionsFiles(): void
    {
        $this->installSkillFor(AgentTarget::ClaudeCode);
        $commandTester = $this->createCommandTester();

        $commandTester->execute([], ['interactive' => false]);

        $this->assertFileDoesNotExist($this->projectDir.'/CLAUDE.md');
        $this->assertFileDoesNotExist($this->projectDir.'/AGENTS.md');
    }

    public function testUpdateNeverTouchesASkillNotInstalledByEasyAdmin(): void
    {
        $this->installSkillFor(AgentTarget::Junie);
        $foreignSkillFilePath = $this->projectDir.'/.claude/skills/easyadmin/SKILL.md';
        $this->filesystem->dumpFile($foreignSkillFilePath, "---\nname: easyadmin\n---\n\n# My own skill\n");
        $commandTester = $this->createCommandTester();

        $exitCode = $commandTester->execute([], ['interactive' => false]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringEqualsFile($foreignSkillFilePath, "---\nname: easyadmin\n---\n\n# My own skill\n");
        $this->assertFileDoesNotExist($this->projectDir.'/.claude/skills/easyadmin/references/api.md');
    }

    public function testUpdateFailsWhenTheInstructionsFileHasUnbalancedMarkers(): void
    {
        $this->installSkillFor(AgentTarget::ClaudeCode);
        $this->filesystem->dumpFile($this->projectDir.'/CLAUDE.md', "<easyadmin-guidelines>\nSome contents.\n");
        $projectFilesBeforeUpdating = $this->snapshotOf($this->projectDir);
        $commandTester = $this->createCommandTester();

        $exitCode = $commandTester->execute([], ['interactive' => false]);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('contains unbalanced "<easyadmin-guidelines>" and "</easyadmin-guidelines>" markers', $this->normalize($commandTester->getDisplay()));
        $this->assertSame($projectFilesBeforeUpdating, $this->snapshotOf($this->projectDir));
    }

    public function testCheckFailsWhenTheInstructionsFileHasUnbalancedMarkers(): void
    {
        $this->installSkillFor(AgentTarget::ClaudeCode);
        $this->filesystem->dumpFile($this->projectDir.'/CLAUDE.md', "<easyadmin-guidelines>\nSome contents.\n");
        $projectFilesBeforeUpdating = $this->snapshotOf($this->projectDir);
        $commandTester = $this->createCommandTester();

        $exitCode = $commandTester->execute(['--check' => true], ['interactive' => false]);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('contains unbalanced "<easyadmin-guidelines>" and "</easyadmin-guidelines>" markers', $this->normalize($commandTester->getDisplay()));
        $this->assertSame($projectFilesBeforeUpdating, $this->snapshotOf($this->projectDir));
    }

    public function testCheckWithAModifiedSkill(): void
    {
        $this->installSkillFor(AgentTarget::ClaudeCode);
        $this->makeInstalledSkillOutdated(AgentTarget::ClaudeCode);
        $projectFilesBeforeUpdating = $this->snapshotOf($this->projectDir);
        $commandTester = $this->createCommandTester();

        $exitCode = $commandTester->execute(['--check' => true], ['interactive' => false]);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('The EasyAdmin skill installed in ".claude/skills/easyadmin" doesn\'t match the files shipped by this package (version 5.5.2-DEV): some of its files were modified or are missing. Run "php bin/console easyadmin:ai:update" to restore it.', $this->normalize($commandTester->getDisplay()));
        $this->assertSame($projectFilesBeforeUpdating, $this->snapshotOf($this->projectDir));
    }

    public function testCheckWithASkillInstalledByAnotherVersion(): void
    {
        $this->installSkillFor(AgentTarget::ClaudeCode);
        $this->makeInstalledSkillComeFromVersion(AgentTarget::ClaudeCode, '5.5.1');
        $projectFilesBeforeUpdating = $this->snapshotOf($this->projectDir);
        $commandTester = $this->createCommandTester();

        $exitCode = $commandTester->execute(['--check' => true], ['interactive' => false]);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('The EasyAdmin skill installed in ".claude/skills/easyadmin" is outdated (installed version: 5.5.1, this package ships 5.5.2-DEV). Run "php bin/console easyadmin:ai:update" to refresh it.', $this->normalize($commandTester->getDisplay()));
        $this->assertSame($projectFilesBeforeUpdating, $this->snapshotOf($this->projectDir));
    }

    public function testCheckWithAnOutdatedGuidelinesBlock(): void
    {
        $this->installSkillFor(AgentTarget::ClaudeCode);
        (new GuidelinesFileWriter())->write($this->projectDir.'/CLAUDE.md', 'Some outdated contents.');
        $projectFilesBeforeUpdating = $this->snapshotOf($this->projectDir);
        $commandTester = $this->createCommandTester();

        $exitCode = $commandTester->execute(['--check' => true], ['interactive' => false]);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('The EasyAdmin section of the "CLAUDE.md" file is outdated. Run "php bin/console easyadmin:ai:update" to refresh it.', $this->normalize($commandTester->getDisplay()));
        $this->assertSame($projectFilesBeforeUpdating, $this->snapshotOf($this->projectDir));
    }

    public function testCheckWhenEverythingIsUpToDate(): void
    {
        $skillInstaller = $this->createSkillInstaller();
        $skillInstaller->install(AgentTarget::ClaudeCode);
        (new GuidelinesFileWriter())->write($this->projectDir.'/CLAUDE.md', $skillInstaller->renderGuidelines('CLAUDE.md'));
        $commandTester = $this->createCommandTester();

        $exitCode = $commandTester->execute(['--check' => true], ['interactive' => false]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertSame('', $commandTester->getDisplay());
    }

    public function testCheckIgnoresInstructionsFilesWithoutTheEasyAdminBlock(): void
    {
        $this->installSkillFor(AgentTarget::ClaudeCode);
        $this->filesystem->dumpFile($this->projectDir.'/CLAUDE.md', "# Project instructions\n");
        $commandTester = $this->createCommandTester();

        $exitCode = $commandTester->execute(['--check' => true], ['interactive' => false]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertSame('', $commandTester->getDisplay());
    }

    private function installSkillFor(AgentTarget $target): void
    {
        $this->createSkillInstaller()->install($target);
    }

    private function makeInstalledSkillOutdated(AgentTarget $target): void
    {
        $this->filesystem->dumpFile($this->projectDir.'/'.$target->skillsDir().'/easyadmin/references/api.md', 'Outdated contents.');
    }

    private function makeInstalledSkillComeFromVersion(AgentTarget $target, string $version): void
    {
        $skillFilePath = $this->projectDir.'/'.$target->skillsDir().'/easyadmin/SKILL.md';
        $skillFileContents = (string) file_get_contents($skillFilePath);

        $this->assertStringContainsString(sprintf("easyadmin-version: '%s'", self::VERSION), $skillFileContents);
        $this->filesystem->dumpFile($skillFilePath, str_replace(sprintf("easyadmin-version: '%s'", self::VERSION), sprintf("easyadmin-version: '%s'", $version), $skillFileContents));
    }

    private function createSkillInstaller(): SkillInstaller
    {
        return new SkillInstaller($this->projectDir, $this->skillsSourceDir(), $this->guidelinesTemplatePath(), self::VERSION);
    }

    private function createCommandTester(): CommandTester
    {
        return new CommandTester(new UpdateAiSkillCommand($this->createSkillInstaller(), new GuidelinesFileWriter()));
    }

    private function normalize(string $display): string
    {
        return trim((string) preg_replace('/\s+/', ' ', $display));
    }
}
