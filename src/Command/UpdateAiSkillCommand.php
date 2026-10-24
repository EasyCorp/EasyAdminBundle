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
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Refreshes the copies of the EasyAdmin skill for AI coding agents already
 * installed in the user project. It's designed to run unattended, so it never
 * asks questions and it never creates files that don't exist yet.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
#[AsCommand(
    name: 'easyadmin:ai:update',
    description: 'Updates the EasyAdmin skill used by AI coding agents',
)]
class UpdateAiSkillCommand extends Command
{
    public function __construct(private readonly SkillInstaller $skillInstaller, private readonly GuidelinesFileWriter $guidelinesFileWriter, ?string $name = null)
    {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->setHelp($this->getCommandHelp())
            ->addOption('check', null, InputOption::VALUE_NONE, 'Report whether the installed skill is outdated without changing any file')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // this command usually runs in Composer scripts; deployments that run
        // "composer install --no-dev" don't need the skill files at all
        if ('0' === getenv('COMPOSER_DEV_MODE')) {
            return Command::SUCCESS;
        }

        $installedTargets = $this->skillInstaller->installedTargets();
        if ([] === $installedTargets) {
            return Command::SUCCESS;
        }

        if (!$this->skillInstaller->sourceExists()) {
            $io->error(sprintf('The AI agent skill files are missing from the EasyAdmin package (expected at "%s"). Reinstall the easycorp/easyadmin-bundle package and run this command again.', $this->skillInstaller->sourceSkillDir()));

            return Command::FAILURE;
        }

        if (true === $input->getOption('check')) {
            return $this->checkInstalledSkill($installedTargets, $io);
        }

        $outdatedTargets = [];
        foreach ($installedTargets as $target) {
            if (SkillStatus::UpToDate !== $this->skillInstaller->status($target)) {
                $outdatedTargets[] = $target;
            }

            $this->skillInstaller->install($target);
        }

        try {
            $updatedInstructionsFiles = $this->updateInstructionsFiles($installedTargets);
        } catch (\RuntimeException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        foreach ($outdatedTargets as $target) {
            $io->text(sprintf('Updated the EasyAdmin skill in "%s".', $this->skillInstaller->relativeSkillDir($target)));
        }

        foreach ($updatedInstructionsFiles as $instructionsFile) {
            $io->text(sprintf('Updated the EasyAdmin section in "%s".', $instructionsFile));
        }

        return Command::SUCCESS;
    }

    /**
     * @param list<AgentTarget> $installedTargets
     */
    private function checkInstalledSkill(array $installedTargets, SymfonyStyle $io): int
    {
        $warningMessages = [];
        foreach ($installedTargets as $target) {
            if (SkillStatus::UpToDate !== $this->skillInstaller->status($target)) {
                $warningMessages[] = $this->getOutdatedSkillMessage($target);
            }
        }

        foreach ($this->getInstructionsFiles($installedTargets) as $instructionsFile => $filePath) {
            try {
                $this->guidelinesFileWriter->validate($filePath);
            } catch (\RuntimeException $e) {
                $warningMessages[] = $e->getMessage();

                continue;
            }

            if ($this->guidelinesFileWriter->hasBlock($filePath) && $this->guidelinesFileWriter->readBlock($filePath) !== $this->skillInstaller->renderGuidelines($instructionsFile)) {
                $warningMessages[] = sprintf('The EasyAdmin section of the "%s" file is outdated. Run "php bin/console easyadmin:ai:update" to refresh it.', $instructionsFile);
            }
        }

        foreach ($warningMessages as $warningMessage) {
            $io->warning($warningMessage);
        }

        return [] === $warningMessages ? Command::SUCCESS : Command::FAILURE;
    }

    private function getOutdatedSkillMessage(AgentTarget $target): string
    {
        $skillDir = $this->skillInstaller->relativeSkillDir($target);
        $installedVersion = $this->skillInstaller->installedVersion($target);
        $packageVersion = $this->skillInstaller->version();

        if (null !== $installedVersion && $installedVersion !== $packageVersion) {
            return sprintf('The EasyAdmin skill installed in "%s" is outdated (installed version: %s, this package ships %s). Run "php bin/console easyadmin:ai:update" to refresh it.', $skillDir, $installedVersion, $packageVersion);
        }

        return sprintf('The EasyAdmin skill installed in "%s" doesn\'t match the files shipped by this package (version %s): some of its files were modified or are missing. Run "php bin/console easyadmin:ai:update" to restore it.', $skillDir, $packageVersion);
    }

    /**
     * @param list<AgentTarget> $installedTargets
     *
     * @return list<string>
     */
    private function updateInstructionsFiles(array $installedTargets): array
    {
        $updatedInstructionsFiles = [];
        foreach ($this->getInstructionsFiles($installedTargets) as $instructionsFile => $filePath) {
            // an opening marker without its closing marker is not a block, so this
            // must run first to report the broken file instead of silently skipping it
            $this->guidelinesFileWriter->validate($filePath);
            if (!$this->guidelinesFileWriter->hasBlock($filePath)) {
                continue;
            }

            if ($this->guidelinesFileWriter->write($filePath, $this->skillInstaller->renderGuidelines($instructionsFile))) {
                $updatedInstructionsFiles[] = $instructionsFile;
            }
        }

        return $updatedInstructionsFiles;
    }

    /**
     * @param list<AgentTarget> $targets
     *
     * @return array<string, string> the name of each instructions file => its absolute path
     */
    private function getInstructionsFiles(array $targets): array
    {
        $instructionsFiles = [];
        foreach ($targets as $target) {
            $instructionsFiles[$target->instructionsFile()] = $this->skillInstaller->guidelinesFilePath($target);
        }

        return $instructionsFiles;
    }

    private function getCommandHelp(): string
    {
        return <<<'HELP'
            The <info>%command.name%</info> command refreshes the EasyAdmin skill for AI coding
            agents already installed in your project, so it always matches the EasyAdmin
            version installed in your application.

            Run it after every EasyAdmin update. Add it to the <info>post-update-cmd</info> script of
            your <info>composer.json</info> file to run it automatically:

              <info>"scripts": { "post-update-cmd": ["@php bin/console easyadmin:ai:update"] }</info>

            The command asks nothing, it only updates the skills installed by
            <info>easyadmin:ai:install</info> and it never creates <info>CLAUDE.md</info> or <info>AGENTS.md</info> files.
            Use the <info>--check</info> option to make the command fail when the installed skill is
            outdated, without changing any file.
            HELP;
    }
}
