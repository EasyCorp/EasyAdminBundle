<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Ai;

/**
 * The AI coding agents that can use the EasyAdmin skill.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
enum AgentTarget: string
{
    case ClaudeCode = 'claude';
    case Agents = 'agents';
    case Junie = 'junie';
    case Windsurf = 'windsurf';

    public function label(): string
    {
        return match ($this) {
            self::ClaudeCode => 'Claude Code (.claude/skills/)',
            self::Agents => 'Codex, Cursor, GitHub Copilot, Gemini CLI, OpenCode and other agents (.agents/skills/)',
            self::Junie => 'JetBrains Junie (.junie/skills/)',
            self::Windsurf => 'Windsurf (.windsurf/skills/)',
        };
    }

    public function skillsDir(): string
    {
        return match ($this) {
            self::ClaudeCode => '.claude/skills',
            self::Agents => '.agents/skills',
            self::Junie => '.junie/skills',
            self::Windsurf => '.windsurf/skills',
        };
    }

    public function instructionsFile(): string
    {
        return match ($this) {
            self::ClaudeCode => 'CLAUDE.md',
            self::Agents, self::Junie, self::Windsurf => 'AGENTS.md',
        };
    }

    /**
     * @return list<string>
     */
    public function detectionPaths(): array
    {
        return match ($this) {
            self::ClaudeCode => ['.claude', 'CLAUDE.md'],
            self::Agents => ['.agents', '.codex', '.cursor', '.github/copilot-instructions.md', '.gemini', 'opencode.json'],
            self::Junie => ['.junie'],
            self::Windsurf => ['.windsurf'],
        };
    }

    public function isDetected(string $projectDir): bool
    {
        foreach ($this->detectionPaths() as $path) {
            if (file_exists($projectDir.'/'.$path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<self>
     */
    public static function detected(string $projectDir): array
    {
        $detectedTargets = [];
        foreach (self::cases() as $target) {
            if ($target->isDetected($projectDir)) {
                $detectedTargets[] = $target;
            }
        }

        return $detectedTargets;
    }

    /**
     * @return list<self>
     */
    public static function defaults(string $projectDir): array
    {
        $detectedTargets = self::detected($projectDir);

        return [] === $detectedTargets ? [self::ClaudeCode, self::Agents] : $detectedTargets;
    }
}
