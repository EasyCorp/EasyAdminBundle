<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Command;

use EasyCorp\Bundle\EasyAdminBundle\Configuration\ConfigManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

/**
 * A command to save the EasyAdmin 2 configuration before migrating to EasyAdmin 3.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class MakeAdminMigrationCommand extends Command
{
    protected static $defaultName = 'make:admin:migration';
    private $configManager;
    private $projectDir;

    public function __construct(ConfigManager $configManager, string $projectDir)
    {
        parent::__construct();

        $this->configManager = $configManager;
        $this->projectDir = $projectDir;
    }

    protected function configure()
    {
        $this
            ->setDescription('Saves EasyAdmin 2 config to later migrate it to EasyAdmin 3 config.')
            ->setHidden(true)
        ;
    }

    /**
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('EasyAdmin 2 to EasyAdmin 3 Migration Assistant');
        $io->text('These are the steps to migrate an EasyAdmin 2 application:');
        $io->newLine();
        $io->listing([
            'Run this command in your project that uses EasyAdmin 2 to backup the backend configuration.',
            'Update your composer.json dependencies to install EasyAdmin 3 (and fix the optional routing/config issues).',
            'Run this command again in your project (which is now running EasyAdmin 3) to generate the new configuration using the previous config backup.',
        ]);

        $backendConfig = $this->configManager->getBackendConfig();

        $backupDir = $this->getBackupDir($input, $output);
        $backupFile = sprintf('%s/easyadmin-config.backup', $backupDir);
        if (file_exists($backupFile)) {
            $overwriteBackupFile = $io->confirm(sprintf('The backup file already exists ("%s"). Do you want to overwrite it?', $backupFile));
            if (false === $overwriteBackupFile) {
                $io->text('<bg=yellow> OK </> If you still want to backup the EasyAdmin 2 config, delete or rename the previous backup file or select a different backup directory and run this command again.');

                return 0;
            }
        }

        (new Filesystem())->dumpFile($backupFile, serialize($backendConfig));

        $io->success(sprintf('The config backup was saved in "%s"', $backupFile));

        $io->section('What\'s next?');
        $io->listing([
            'Now, update the <info>"easycorp/easyadmin-bundle"</> dependency to <info>"^3.0"</> in your <info>composer.json</> file.',
            'Run the <info>"composer update"</> command to update dependencies.',
            'Depending on your project you may need to fix some routing/config issues.',
            'If you need help, read https://symfony.com/...',
            'Finally, run this command again in your project to generate the EasyAdmin 3 files using the EasyAdmin 2 config backup.',
        ]);

        return 0;
    }

    private function getBackupDir(InputInterface $input, OutputInterface $output): string
    {
        // code copied from https://symfony.com/blog/new-in-symfony-4-3-better-console-autocomplete
        $callback = function (string $userInput): array {
            // Strip any characters from the last slash to the end of the string
            // to keep only the last directory and generate suggestions for it
            $inputPath = preg_replace('%(/|^)[^/]*$%', '$1', $userInput);
            $inputPath = '' === $inputPath ? '.' : $inputPath;
            $foundFilesAndDirs = @scandir($inputPath, SCANDIR_SORT_ASCENDING) ?: [];

            return array_map(function ($dirOrFile) use ($inputPath) {
                return $inputPath.$dirOrFile;
            }, $foundFilesAndDirs);
        };

        $defaultBackupDir = realpath($this->projectDir);
        $helper = $this->getHelper('question');
        $question = new Question(sprintf(" <info>In which directory do you want to store the config backup?</>\n <comment>[default: %s]</>\n > ", $defaultBackupDir), $defaultBackupDir);
        $question->setAutocompleterCallback($callback);

        $backupDir = $helper->ask($input, $output, $question);
        $output->writeln('');

        return $backupDir;
    }
}
