<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Ai;

use EasyCorp\Bundle\EasyAdminBundle\Ai\AgentTarget;

class AgentTargetTest extends AiTestCase
{
    /**
     * @dataProvider provideAgentTargets
     */
    public function testTargetConfiguration(string $slug, string $skillsDir, string $instructionsFile, string $label): void
    {
        $target = AgentTarget::from($slug);

        $this->assertSame($slug, $target->value);
        $this->assertSame($skillsDir, $target->skillsDir());
        $this->assertSame($instructionsFile, $target->instructionsFile());
        $this->assertSame($label, $target->label());
    }

    public static function provideAgentTargets(): \Generator
    {
        yield 'claude' => ['claude', '.claude/skills', 'CLAUDE.md', 'Claude Code (.claude/skills/)'];
        yield 'agents' => ['agents', '.agents/skills', 'AGENTS.md', 'Codex, Cursor, GitHub Copilot, Gemini CLI, OpenCode and other agents (.agents/skills/)'];
        yield 'junie' => ['junie', '.junie/skills', 'AGENTS.md', 'JetBrains Junie (.junie/skills/)'];
        yield 'windsurf' => ['windsurf', '.windsurf/skills', 'AGENTS.md', 'Windsurf (.windsurf/skills/)'];
    }

    public function testUnknownSlug(): void
    {
        $this->assertNull(AgentTarget::tryFrom('copilot'));
    }

    /**
     * @dataProvider provideDetectionPaths
     */
    public function testDetection(string $slug, string $detectionPath): void
    {
        $this->createDetectionPath($detectionPath);

        $target = AgentTarget::from($slug);
        $this->assertTrue($target->isDetected($this->projectDir));
        $this->assertContains($target, AgentTarget::detected($this->projectDir));
        $this->assertSame([$target], AgentTarget::defaults($this->projectDir));
    }

    public static function provideDetectionPaths(): \Generator
    {
        foreach (AgentTarget::cases() as $target) {
            foreach ($target->detectionPaths() as $detectionPath) {
                yield $target->value.': '.$detectionPath => [$target->value, $detectionPath];
            }
        }
    }

    public function testNothingIsDetectedInAnEmptyProject(): void
    {
        $this->assertSame([], AgentTarget::detected($this->projectDir));
    }

    public function testDefaultsWithoutDetectedAgents(): void
    {
        $this->assertSame([AgentTarget::ClaudeCode, AgentTarget::Agents], AgentTarget::defaults($this->projectDir));
    }

    public function testDefaultsFollowTheEnumOrder(): void
    {
        $this->createDetectionPath('.windsurf');
        $this->createDetectionPath('.claude');

        $this->assertSame([AgentTarget::ClaudeCode, AgentTarget::Windsurf], AgentTarget::defaults($this->projectDir));
    }

    private function createDetectionPath(string $detectionPath): void
    {
        if (str_ends_with($detectionPath, '.md') || str_ends_with($detectionPath, '.json')) {
            $this->filesystem->dumpFile($this->projectDir.'/'.$detectionPath, '');

            return;
        }

        $this->filesystem->mkdir($this->projectDir.'/'.$detectionPath);
    }
}
