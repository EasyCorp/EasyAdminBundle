<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Command;

use EasyCorp\Bundle\EasyAdminBundle\Ai\AgentTarget;
use EasyCorp\Bundle\EasyAdminBundle\Ai\GuidelinesFileWriter;
use EasyCorp\Bundle\EasyAdminBundle\Ai\SkillInstaller;
use EasyCorp\Bundle\EasyAdminBundle\Ai\SkillStatus;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Copies the EasyAdmin skill for AI coding agents into the user project.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
#[AsCommand(
    name: 'easyadmin:ai:install',
    description: 'Installs the EasyAdmin skill used by AI coding agents',
)]
class InstallAiSkillCommand extends Command
{
    public function __construct(private readonly SkillInstaller $skillInstaller, private readonly GuidelinesFileWriter $guidelinesFileWriter, ?string $name = null)
    {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->setHelp($this->getCommandHelp())
            ->addOption('agent', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, sprintf('The AI coding agent to install the skill for; repeat this option to select several agents (%s)', implode(', ', self::getAgentSlugs())))
            ->addOption('all', null, InputOption::VALUE_NONE, 'Install the skill for all the supported AI coding agents')
            ->addOption('skip-guidelines', null, InputOption::VALUE_NONE, 'Do not add the EasyAdmin section to the AI instructions files (CLAUDE.md, AGENTS.md)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$this->skillInstaller->sourceExists()) {
            $io->error(sprintf('The AI agent skill files are missing from the EasyAdmin package (expected at "%s"). Reinstall the easycorp/easyadmin-bundle package and run this command again.', $this->skillInstaller->sourceSkillDir()));

            return Command::FAILURE;
        }

        $targets = $this->getSelectedTargets($input, $io);
        if (null === $targets) {
            return Command::FAILURE;
        }

        $addGuidelines = true !== $input->getOption('skip-guidelines');
        if (!$this->isSafeToWrite($targets, $addGuidelines, $io)) {
            return Command::FAILURE;
        }

        $writtenFiles = [];
        foreach ($targets as $target) {
            $writtenFiles = array_merge($writtenFiles, $this->skillInstaller->install($target));
        }

        if ($addGuidelines && $io->confirm('Do you want to add a short EasyAdmin section to your AI instructions files (CLAUDE.md, AGENTS.md)? <options=bold>[recommended]</>', true)) {
            foreach ($this->getInstructionsFiles($targets) as $instructionsFile) {
                $filePath = $this->skillInstaller->guidelinesFilePath($this->getFirstTargetFor($targets, $instructionsFile));
                if ($this->guidelinesFileWriter->write($filePath, $this->skillInstaller->renderGuidelines($instructionsFile))) {
                    $writtenFiles[] = $instructionsFile;
                }
            }
        }

        $io->success('The EasyAdmin skill for AI coding agents has been successfully installed.');
        $io->text('Next steps:');
        $io->listing(array_merge($writtenFiles, [
            'Restart your AI coding agent so it picks up the new skill.',
            'Keep the skill in sync with future EasyAdmin updates by adding this to the "scripts" section of your composer.json file:',
        ]));
        $io->text([
            '      "post-update-cmd": [',
            '          "@auto-scripts",',
            '          "@php bin/console easyadmin:ai:update"',
            '      ]',
        ]);
        $io->newLine();

        return Command::SUCCESS;
    }

    /**
     * @return list<AgentTarget>|null null when the agents given in the --agent option are not valid
     */
    private function getSelectedTargets(InputInterface $input, SymfonyStyle $io): ?array
    {
        if (true === $input->getOption('all')) {
            return AgentTarget::cases();
        }

        /** @var list<string> $selectedAgents */
        $selectedAgents = $input->getOption('agent');
        if ([] !== $selectedAgents) {
            $targets = [];
            foreach ($selectedAgents as $selectedAgent) {
                $target = AgentTarget::tryFrom($selectedAgent);
                if (null === $target) {
                    $io->error(sprintf('The "%s" value given in the "--agent" option is not a supported AI coding agent. Use one of the following values: %s.', $selectedAgent, implode(', ', self::getAgentSlugs())));

                    return null;
                }

                $targets[] = $target;
            }

            return self::getUniqueTargets($targets);
        }

        $defaultTargets = $this->skillInstaller->defaultTargets();
        if (!$input->isInteractive()) {
            return $defaultTargets;
        }

        $choices = [];
        foreach (AgentTarget::cases() as $target) {
            $choices[$target->value] = $target->label();
        }

        $io->text('The preselected agents are the ones detected in your project, or the most common ones when no agent is detected. Press Enter to accept them or type the agents you want, separated by commas.');
        $question = new ChoiceQuestion(
            'Which AI coding agents do you want to install the EasyAdmin skill for?',
            $choices,
            implode(',', array_map(static fn (AgentTarget $target): string => $target->value, $defaultTargets))
        );
        $question->setMultiselect(true);

        $targets = [];
        foreach ((array) $io->askQuestion($question) as $selectedAgent) {
            $target = AgentTarget::tryFrom((string) $selectedAgent);
            if (null !== $target) {
                $targets[] = $target;
            }
        }

        return self::getUniqueTargets($targets);
    }

    /**
     * @param list<AgentTarget> $targets
     */
    private function isSafeToWrite(array $targets, bool $addGuidelines, SymfonyStyle $io): bool
    {
        $errorMessages = [];
        foreach ($targets as $target) {
            $skillDir = $this->skillInstaller->relativeSkillDir($target);

            if (SkillStatus::NotManagedByEasyAdmin === $this->skillInstaller->status($target)) {
                $errorMessages[] = sprintf('The "%s" directory already contains an "easyadmin" skill that EasyAdmin didn\'t install, so this command won\'t overwrite it. Delete that directory and run this command again, or keep it and skip this agent.', $skillDir);

                continue;
            }

            if (!self::isWritableLocation($this->skillInstaller->skillDir($target))) {
                $errorMessages[] = sprintf('The "%s" directory can\'t be created or modified because some of its parent directories are not writable. Fix those permissions and run this command again.', $skillDir);
            }
        }

        if ($addGuidelines) {
            foreach ($this->getInstructionsFiles($targets) as $instructionsFile) {
                try {
                    $this->guidelinesFileWriter->validate($this->skillInstaller->guidelinesFilePath($this->getFirstTargetFor($targets, $instructionsFile)));
                } catch (\RuntimeException $e) {
                    $errorMessages[] = $e->getMessage();
                }
            }
        }

        foreach ($errorMessages as $errorMessage) {
            $io->error($errorMessage);
        }

        return [] === $errorMessages;
    }

    /**
     * @param list<AgentTarget> $targets
     *
     * @return list<string>
     */
    private function getInstructionsFiles(array $targets): array
    {
        $instructionsFiles = [];
        foreach ($targets as $target) {
            if (!\in_array($target->instructionsFile(), $instructionsFiles, true)) {
                $instructionsFiles[] = $target->instructionsFile();
            }
        }

        return $instructionsFiles;
    }

    /**
     * @param list<AgentTarget> $targets
     */
    private function getFirstTargetFor(array $targets, string $instructionsFile): AgentTarget
    {
        foreach ($targets as $target) {
            if ($target->instructionsFile() === $instructionsFile) {
                return $target;
            }
        }

        throw new \InvalidArgumentException(sprintf('None of the selected AI coding agents uses the "%s" instructions file.', $instructionsFile));
    }

    /**
     * @param list<AgentTarget> $targets
     *
     * @return list<AgentTarget>
     */
    private static function getUniqueTargets(array $targets): array
    {
        $uniqueTargets = [];
        foreach ($targets as $target) {
            if (!\in_array($target, $uniqueTargets, true)) {
                $uniqueTargets[] = $target;
            }
        }

        return $uniqueTargets;
    }

    private static function isWritableLocation(string $path): bool
    {
        while (!file_exists($path)) {
            $parentPath = \dirname($path);
            if ($parentPath === $path) {
                return false;
            }

            $path = $parentPath;
        }

        return is_writable($path);
    }

    /**
     * @return list<string>
     */
    private static function getAgentSlugs(): array
    {
        return array_map(static fn (AgentTarget $target): string => $target->value, AgentTarget::cases());
    }

    private function getCommandHelp(): string
    {
        return <<<'HELP'
            The <info>%command.name%</info> command copies the EasyAdmin skill for AI coding
            agents into your project, so your agent generates code that matches the
            EasyAdmin version installed in your application.

            The command asks which AI coding agents to install the skill for. The agents
            detected in your project are preselected. Use the <info>--agent</info> option to select
            them without answering questions, or <info>--all</info> to select every supported agent:

              <info>php %command.full_name% --agent=claude --agent=agents</info>
              <info>php %command.full_name% --all</info>

            It also adds a short EasyAdmin section to your AI instructions files
            (<info>CLAUDE.md</info> and <info>AGENTS.md</info>) between two markers, keeping the rest of those
            files untouched. Use the <info>--skip-guidelines</info> option to skip that step.

            This command never overwrites an "easyadmin" skill that it didn't install, so
            you can run it safely as many times as needed.
            HELP;
    }
}
