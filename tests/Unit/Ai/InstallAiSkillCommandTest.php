<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Ai;

use EasyCorp\Bundle\EasyAdminBundle\Ai\GuidelinesFileWriter;
use EasyCorp\Bundle\EasyAdminBundle\Ai\SkillInstaller;
use EasyCorp\Bundle\EasyAdminBundle\Command\InstallAiSkillCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class InstallAiSkillCommandTest extends AiTestCase
{
    public function testInstallForAllAgents(): void
    {
        $commandTester = $this->createCommandTester();

        $exitCode = $commandTester->execute(['--all' => true], ['interactive' => false]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertFileExists($this->projectDir.'/.claude/skills/easyadmin/SKILL.md');
        $this->assertFileExists($this->projectDir.'/.agents/skills/easyadmin/SKILL.md');
        $this->assertFileExists($this->projectDir.'/.junie/skills/easyadmin/SKILL.md');
        $this->assertFileExists($this->projectDir.'/.windsurf/skills/easyadmin/SKILL.md');
        $this->assertFileExists($this->projectDir.'/.claude/skills/easyadmin/references/api.md');
        $this->assertStringContainsString('<easyadmin-guidelines>', file_get_contents($this->projectDir.'/CLAUDE.md'));
        $this->assertStringContainsString('<easyadmin-guidelines>', file_get_contents($this->projectDir.'/AGENTS.md'));
        $this->assertStringContainsString('The EasyAdmin skill for AI coding agents has been successfully installed.', $this->normalize($commandTester->getDisplay()));
    }

    public function testInstallForTheGivenAgents(): void
    {
        $commandTester = $this->createCommandTester();

        $exitCode = $commandTester->execute(['--agent' => ['claude', 'junie', 'claude']], ['interactive' => false]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertFileExists($this->projectDir.'/.claude/skills/easyadmin/SKILL.md');
        $this->assertFileExists($this->projectDir.'/.junie/skills/easyadmin/SKILL.md');
        $this->assertDirectoryDoesNotExist($this->projectDir.'/.agents');
        $this->assertDirectoryDoesNotExist($this->projectDir.'/.windsurf');

        // the AGENTS.md block points to the only agent installed that reads that file
        $this->assertStringContainsString('`.junie/skills/easyadmin/SKILL.md`', file_get_contents($this->projectDir.'/AGENTS.md'));
    }

    public function testInstallForAnUnknownAgent(): void
    {
        $commandTester = $this->createCommandTester();

        $exitCode = $commandTester->execute(['--agent' => ['copilot']], ['interactive' => false]);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('The "copilot" value given in the "--agent" option is not a supported AI coding agent. Use one of the following values: claude, agents, junie, windsurf.', $this->normalize($commandTester->getDisplay()));
        $this->assertSame([], $this->snapshotOf($this->projectDir));
    }

    public function testInstallWithoutDetectedAgents(): void
    {
        $commandTester = $this->createCommandTester();

        $exitCode = $commandTester->execute([], ['interactive' => false]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertFileExists($this->projectDir.'/.claude/skills/easyadmin/SKILL.md');
        $this->assertFileExists($this->projectDir.'/.agents/skills/easyadmin/SKILL.md');
        $this->assertDirectoryDoesNotExist($this->projectDir.'/.junie');
        $this->assertDirectoryDoesNotExist($this->projectDir.'/.windsurf');
    }

    public function testInstallForTheDetectedAgents(): void
    {
        $this->filesystem->mkdir($this->projectDir.'/.junie');
        $commandTester = $this->createCommandTester();

        $exitCode = $commandTester->execute([], ['interactive' => false]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertFileExists($this->projectDir.'/.junie/skills/easyadmin/SKILL.md');
        $this->assertDirectoryDoesNotExist($this->projectDir.'/.claude');
        $this->assertDirectoryDoesNotExist($this->projectDir.'/.agents');
    }

    public function testInstallInteractively(): void
    {
        $commandTester = $this->createCommandTester();
        $commandTester->setInputs(['claude,agents', 'yes']);

        $exitCode = $commandTester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertFileExists($this->projectDir.'/.claude/skills/easyadmin/SKILL.md');
        $this->assertFileExists($this->projectDir.'/.agents/skills/easyadmin/SKILL.md');
        $this->assertDirectoryDoesNotExist($this->projectDir.'/.windsurf');
        $this->assertFileExists($this->projectDir.'/CLAUDE.md');
        $this->assertFileExists($this->projectDir.'/AGENTS.md');
        $this->assertStringContainsString('Which AI coding agents do you want to install the EasyAdmin skill for?', $this->normalize($commandTester->getDisplay()));
    }

    public function testInstallInteractivelyAcceptingThePreselectedAgents(): void
    {
        $commandTester = $this->createCommandTester();
        $commandTester->setInputs(['', 'yes']);

        $exitCode = $commandTester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertFileExists($this->projectDir.'/.claude/skills/easyadmin/SKILL.md');
        $this->assertFileExists($this->projectDir.'/.agents/skills/easyadmin/SKILL.md');
        $this->assertDirectoryDoesNotExist($this->projectDir.'/.junie');
        $this->assertDirectoryDoesNotExist($this->projectDir.'/.windsurf');
        $this->assertFileExists($this->projectDir.'/CLAUDE.md');
        $this->assertFileExists($this->projectDir.'/AGENTS.md');
    }

    public function testInstallInAReadOnlyDirectory(): void
    {
        if ('\\' === \DIRECTORY_SEPARATOR) {
            $this->markTestSkipped('Directory permissions work differently on Windows.');
        }

        if (\function_exists('posix_getuid') && 0 === posix_getuid()) {
            $this->markTestSkipped('The root user can write in read-only directories.');
        }

        $this->filesystem->mkdir($this->projectDir.'/.claude');
        $this->filesystem->chmod($this->projectDir.'/.claude', 0555);
        $commandTester = $this->createCommandTester();

        try {
            $exitCode = $commandTester->execute(['--agent' => ['claude'], '--skip-guidelines' => true], ['interactive' => false]);

            $this->assertSame(Command::FAILURE, $exitCode);
            $this->assertStringContainsString('The ".claude/skills/easyadmin" directory can\'t be created or modified because some of its parent directories are not writable. Fix those permissions and run this command again.', $this->normalize($commandTester->getDisplay()));
            $this->assertDirectoryDoesNotExist($this->projectDir.'/.claude/skills');
        } finally {
            $this->filesystem->chmod($this->projectDir.'/.claude', 0755);
        }
    }

    public function testInstallInteractivelyWithoutAddingTheGuidelines(): void
    {
        $commandTester = $this->createCommandTester();
        $commandTester->setInputs(['windsurf', 'no']);

        $exitCode = $commandTester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertFileExists($this->projectDir.'/.windsurf/skills/easyadmin/SKILL.md');
        $this->assertFileDoesNotExist($this->projectDir.'/AGENTS.md');
    }

    public function testInstallWithoutGuidelines(): void
    {
        $commandTester = $this->createCommandTester();

        $exitCode = $commandTester->execute(['--all' => true, '--skip-guidelines' => true], ['interactive' => false]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertFileExists($this->projectDir.'/.claude/skills/easyadmin/SKILL.md');
        $this->assertFileDoesNotExist($this->projectDir.'/CLAUDE.md');
        $this->assertFileDoesNotExist($this->projectDir.'/AGENTS.md');
    }

    public function testInstallDoesNotOverwriteASkillNotInstalledByEasyAdmin(): void
    {
        $this->filesystem->dumpFile($this->projectDir.'/.agents/skills/easyadmin/SKILL.md', "---\nname: easyadmin\n---\n\n# My own skill\n");
        $projectFilesBeforeInstalling = $this->snapshotOf($this->projectDir);
        $commandTester = $this->createCommandTester();

        $exitCode = $commandTester->execute(['--all' => true], ['interactive' => false]);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('The ".agents/skills/easyadmin" directory already contains an "easyadmin" skill that EasyAdmin didn\'t install, so this command won\'t overwrite it. Delete that directory and run this command again, or keep it and skip this agent.', $this->normalize($commandTester->getDisplay()));
        $this->assertSame($projectFilesBeforeInstalling, $this->snapshotOf($this->projectDir));
    }

    public function testInstallDoesNotWriteAnythingWhenTheInstructionsFileHasUnbalancedMarkers(): void
    {
        $this->filesystem->dumpFile($this->projectDir.'/CLAUDE.md', "<easyadmin-guidelines>\nSome contents.\n");
        $projectFilesBeforeInstalling = $this->snapshotOf($this->projectDir);
        $commandTester = $this->createCommandTester();

        $exitCode = $commandTester->execute(['--all' => true], ['interactive' => false]);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('contains unbalanced "<easyadmin-guidelines>" and "</easyadmin-guidelines>" markers', $this->normalize($commandTester->getDisplay()));
        $this->assertSame($projectFilesBeforeInstalling, $this->snapshotOf($this->projectDir));
    }

    public function testInstallWhenTheSkillIsMissingFromThePackage(): void
    {
        $this->filesystem->remove($this->skillsSourceDir());
        $commandTester = $this->createCommandTester();

        $exitCode = $commandTester->execute(['--all' => true], ['interactive' => false]);

        $this->assertSame(Command::FAILURE, $exitCode);
        $display = $this->normalize($commandTester->getDisplay());
        $this->assertStringContainsString('The AI agent skill files are missing from the EasyAdmin package', $display);
        $this->assertStringContainsString('Reinstall the easycorp/easyadmin-bundle package and run this command again.', $display);
        $this->assertSame([], $this->snapshotOf($this->projectDir));
    }

    public function testInstallIsIdempotent(): void
    {
        $this->createCommandTester()->execute(['--all' => true], ['interactive' => false]);
        $projectFilesAfterFirstInstall = $this->snapshotOf($this->projectDir);

        $this->createCommandTester()->execute(['--all' => true], ['interactive' => false]);

        $this->assertSame($projectFilesAfterFirstInstall, $this->snapshotOf($this->projectDir));
    }

    private function createCommandTester(): CommandTester
    {
        return new CommandTester(new InstallAiSkillCommand(
            new SkillInstaller($this->projectDir, $this->skillsSourceDir(), $this->guidelinesTemplatePath(), self::VERSION),
            new GuidelinesFileWriter()
        ));
    }

    private function normalize(string $display): string
    {
        return trim((string) preg_replace('/\s+/', ' ', $display));
    }
}
