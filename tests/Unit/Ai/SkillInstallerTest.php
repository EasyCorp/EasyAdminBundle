<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Ai;

use EasyCorp\Bundle\EasyAdminBundle\Ai\AgentTarget;
use EasyCorp\Bundle\EasyAdminBundle\Ai\SkillInstaller;
use EasyCorp\Bundle\EasyAdminBundle\Ai\SkillStatus;

class SkillInstallerTest extends AiTestCase
{
    private SkillInstaller $skillInstaller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skillInstaller = $this->createSkillInstaller();
    }

    /**
     * @dataProvider provideAgentTargets
     */
    public function testInstall(AgentTarget $target, string $expectedSkillDir): void
    {
        $writtenFiles = $this->skillInstaller->install($target);

        $this->assertSame([$expectedSkillDir.'/SKILL.md', $expectedSkillDir.'/references/api.md'], $writtenFiles);
        $this->assertFileExists($this->projectDir.'/'.$expectedSkillDir.'/SKILL.md');
        $this->assertStringEqualsFile(
            $this->projectDir.'/'.$expectedSkillDir.'/references/api.md',
            file_get_contents($this->skillsSourceDir().'/easyadmin/references/api.md')
        );
        $this->assertSame($expectedSkillDir, $this->skillInstaller->relativeSkillDir($target));
        $this->assertSame($this->projectDir.'/'.$expectedSkillDir, $this->skillInstaller->skillDir($target));
    }

    public static function provideAgentTargets(): \Generator
    {
        yield 'claude' => [AgentTarget::ClaudeCode, '.claude/skills/easyadmin'];
        yield 'agents' => [AgentTarget::Agents, '.agents/skills/easyadmin'];
        yield 'junie' => [AgentTarget::Junie, '.junie/skills/easyadmin'];
        yield 'windsurf' => [AgentTarget::Windsurf, '.windsurf/skills/easyadmin'];
    }

    public function testInstallAddsTheInstallationMetadata(): void
    {
        $this->skillInstaller->install(AgentTarget::ClaudeCode);

        $this->assertStringEqualsFile($this->projectDir.'/.claude/skills/easyadmin/SKILL.md', <<<'MD'
            ---
            name: easyadmin
            description: >-
              Creates and modifies EasyAdmin admin backends in Symfony applications.
            license: MIT
            metadata:
              author: EasyCorp
              easyadmin-version: '5.5.2-DEV'
              installed-by: 'easyadmin:ai:install'
            ---

            # EasyAdmin

            Use the makers to generate dashboards and CRUD controllers.

            MD);

        $this->assertSame(self::VERSION, $this->skillInstaller->installedVersion(AgentTarget::ClaudeCode));
        $this->assertSame([AgentTarget::ClaudeCode], $this->skillInstaller->installedTargets());
    }

    public function testInstallAddsTheMetadataKeyWhenTheFrontmatterDoesNotHaveIt(): void
    {
        $this->filesystem->dumpFile($this->skillsSourceDir().'/easyadmin/SKILL.md', "---\nname: easyadmin\n---\n\n# EasyAdmin\n");

        $this->skillInstaller->install(AgentTarget::ClaudeCode);

        $this->assertStringEqualsFile($this->projectDir.'/.claude/skills/easyadmin/SKILL.md', <<<'MD'
            ---
            name: easyadmin
            metadata:
              easyadmin-version: '5.5.2-DEV'
              installed-by: 'easyadmin:ai:install'
            ---

            # EasyAdmin

            MD);
    }

    public function testInstallIgnoresTheKeysThatFollowTheMetadataKey(): void
    {
        $this->filesystem->dumpFile($this->skillsSourceDir().'/easyadmin/SKILL.md', "---\nmetadata:\n  author: EasyCorp\nname: easyadmin\n---\n\n# EasyAdmin\n");

        $this->skillInstaller->install(AgentTarget::ClaudeCode);

        $this->assertStringEqualsFile($this->projectDir.'/.claude/skills/easyadmin/SKILL.md', <<<'MD'
            ---
            metadata:
              author: EasyCorp
              easyadmin-version: '5.5.2-DEV'
              installed-by: 'easyadmin:ai:install'
            name: easyadmin
            ---

            # EasyAdmin

            MD);
    }

    public function testInstallIsIdempotent(): void
    {
        $this->skillInstaller->install(AgentTarget::ClaudeCode);
        $firstInstallFiles = $this->snapshotOf($this->projectDir);

        $this->skillInstaller->install(AgentTarget::ClaudeCode);

        $this->assertSame($firstInstallFiles, $this->snapshotOf($this->projectDir));
        $this->assertSame(SkillStatus::UpToDate, $this->skillInstaller->status(AgentTarget::ClaudeCode));
    }

    public function testInstallRemovesTheFilesNoLongerIncludedInTheSkill(): void
    {
        $this->skillInstaller->install(AgentTarget::ClaudeCode);
        $skillDir = $this->projectDir.'/.claude/skills/easyadmin';
        $this->filesystem->dumpFile($skillDir.'/references/removed.md', 'This reference no longer exists.');
        $this->filesystem->dumpFile($skillDir.'/examples/removed.md', 'This example no longer exists.');

        $this->skillInstaller->install(AgentTarget::ClaudeCode);

        $this->assertFileDoesNotExist($skillDir.'/references/removed.md');
        $this->assertDirectoryDoesNotExist($skillDir.'/examples');
        $this->assertFileExists($skillDir.'/references/api.md');
    }

    public function testStatusOfANotInstalledSkill(): void
    {
        $this->assertSame(SkillStatus::NotInstalled, $this->skillInstaller->status(AgentTarget::ClaudeCode));
        $this->assertNull($this->skillInstaller->installedVersion(AgentTarget::ClaudeCode));
        $this->assertSame([], $this->skillInstaller->installedTargets());
    }

    public function testStatusOfAnEmptySkillDirectory(): void
    {
        $this->filesystem->mkdir($this->projectDir.'/.claude/skills/easyadmin');

        $this->assertSame(SkillStatus::NotInstalled, $this->skillInstaller->status(AgentTarget::ClaudeCode));
    }

    public function testStatusOfAnOutdatedSkill(): void
    {
        $this->skillInstaller->install(AgentTarget::ClaudeCode);
        $this->filesystem->dumpFile($this->projectDir.'/.claude/skills/easyadmin/references/api.md', 'Outdated contents.');

        $this->assertSame(SkillStatus::Outdated, $this->skillInstaller->status(AgentTarget::ClaudeCode));
        $this->assertSame([AgentTarget::ClaudeCode], $this->skillInstaller->installedTargets());
    }

    public function testStatusOfASkillWithSomeExtraFile(): void
    {
        $this->skillInstaller->install(AgentTarget::ClaudeCode);
        $this->filesystem->dumpFile($this->projectDir.'/.claude/skills/easyadmin/references/extra.md', 'Some extra file.');

        $this->assertSame(SkillStatus::Outdated, $this->skillInstaller->status(AgentTarget::ClaudeCode));
    }

    public function testStatusOfASkillNotInstalledByEasyAdmin(): void
    {
        $this->filesystem->dumpFile($this->projectDir.'/.claude/skills/easyadmin/SKILL.md', "---\nname: easyadmin\n---\n\n# My own skill\n");

        $this->assertSame(SkillStatus::NotManagedByEasyAdmin, $this->skillInstaller->status(AgentTarget::ClaudeCode));
        $this->assertSame([], $this->skillInstaller->installedTargets());
        $this->assertNull($this->skillInstaller->installedVersion(AgentTarget::ClaudeCode));
    }

    public function testStatusOfASkillWithoutTheSkillFile(): void
    {
        $this->filesystem->dumpFile($this->projectDir.'/.claude/skills/easyadmin/references/api.md', 'Some contents.');

        $this->assertSame(SkillStatus::NotManagedByEasyAdmin, $this->skillInstaller->status(AgentTarget::ClaudeCode));
    }

    public function testRemovingTheInstalledByLineGivesTheSkillBackToTheUser(): void
    {
        $this->skillInstaller->install(AgentTarget::ClaudeCode);
        $skillFilePath = $this->projectDir.'/.claude/skills/easyadmin/SKILL.md';
        $this->filesystem->dumpFile($skillFilePath, preg_replace('/^.*installed-by:.*\n/m', '', file_get_contents($skillFilePath)));

        $this->assertSame(SkillStatus::NotManagedByEasyAdmin, $this->skillInstaller->status(AgentTarget::ClaudeCode));
        $this->assertSame([], $this->skillInstaller->installedTargets());
    }

    public function testInstalledTargetsOnlyIncludesTheSkillsInstalledByEasyAdmin(): void
    {
        $this->skillInstaller->install(AgentTarget::Junie);
        $this->skillInstaller->install(AgentTarget::Windsurf);
        $this->filesystem->dumpFile($this->projectDir.'/.claude/skills/easyadmin/SKILL.md', "---\nname: easyadmin\n---\n\n# My own skill\n");

        $this->assertSame([AgentTarget::Junie, AgentTarget::Windsurf], $this->skillInstaller->installedTargets());
    }

    public function testMissingSkillSource(): void
    {
        $this->filesystem->remove($this->skillsSourceDir());

        $this->assertFalse($this->skillInstaller->sourceExists());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(sprintf('The AI agent skill files were not found at "%s".', $this->skillsSourceDir().'/easyadmin'));

        $this->skillInstaller->renderSkillFiles();
    }

    public function testRenderGuidelines(): void
    {
        $this->assertSame(
            "This project uses EasyAdmin 5.5.2-DEV.\n\nRead and follow the `easyadmin` skill at `.claude/skills/easyadmin/SKILL.md`.",
            $this->skillInstaller->renderGuidelines('CLAUDE.md')
        );
        $this->assertSame(
            "This project uses EasyAdmin 5.5.2-DEV.\n\nRead and follow the `easyadmin` skill at `.agents/skills/easyadmin/SKILL.md`.",
            $this->skillInstaller->renderGuidelines('AGENTS.md')
        );
    }

    public function testRenderGuidelinesUsesTheSkillDirectoryOfTheInstalledAgent(): void
    {
        $this->skillInstaller->install(AgentTarget::Junie);

        $this->assertStringContainsString('`.junie/skills/easyadmin/SKILL.md`', $this->skillInstaller->renderGuidelines('AGENTS.md'));
    }

    public function testRenderGuidelinesForAnUnknownInstructionsFile(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->skillInstaller->renderGuidelines('COPILOT.md');
    }

    public function testGuidelinesFilePath(): void
    {
        $this->assertSame($this->projectDir.'/CLAUDE.md', $this->skillInstaller->guidelinesFilePath(AgentTarget::ClaudeCode));
        $this->assertSame($this->projectDir.'/AGENTS.md', $this->skillInstaller->guidelinesFilePath(AgentTarget::Windsurf));
    }

    public function testDefaultTargets(): void
    {
        $this->assertSame([AgentTarget::ClaudeCode, AgentTarget::Agents], $this->skillInstaller->defaultTargets());

        $this->filesystem->mkdir($this->projectDir.'/.junie');

        $this->assertSame([AgentTarget::Junie], $this->createSkillInstaller()->defaultTargets());
    }

    private function createSkillInstaller(): SkillInstaller
    {
        return new SkillInstaller($this->projectDir, $this->skillsSourceDir(), $this->guidelinesTemplatePath(), self::VERSION);
    }
}
