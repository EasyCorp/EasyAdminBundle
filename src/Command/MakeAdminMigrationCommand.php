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
            ->setDescription('Exports EasyAdmin 2 config to later migrate it to EasyAdmin 3 config.')
        ;
    }

    /**
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('EasyAdmin 2 to EasyAdmin 3 Migration Assistant');
        $io->text('This command will export your EasyAdmin 2 YAML configuration. Later, after upgrading to EasyAdmin 3, that configuration will be used to generate the new PHP code needed by EasyAdmin 3.');
        $io->newLine();

        $backendConfig = $this->configManager->getBackendConfig();

        $backupDir = $this->getBackupDir($input, $output);
        if (!is_dir($backupDir)) {
            $io->error(sprintf('The given path ("%s") is not a directory or it doesn\'t exist. Create that directory or use another existing directory and then run this command again.', $backupDir));

            return 1;
        }

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
            "1) Update the <info>easycorp/easyadmin-bundle</> dependency to <info>^3.0</> in your <info>composer.json</> file.\n",
            "2) Run the <info>composer update easycorp/easyadmin-bundle</> command to update EasyAdmin (or run just <info>composer update</> to update all dependencies).\n",
            "3) Depending on your project config, you may see some errors after upgrading. They are caused by config files which still reference old EasyAdmin 2 files (such as 'EasyAdminController'). Comment that config or remove those files because you won\'t need them anymore.\n",
            "4) If you need help, read https://symfony.com/doc/master/bundles/EasyAdminBundle/upgrade.html or visit the #easyadminbundle channel on https://symfony.com/slack\n",
            '5) Once all issues are fixed, run this exact command again in your project to generate the EasyAdmin 3 files using the EasyAdmin 2 config backup.',
        ]);

        return 0;
    }

    private function getBackupDir(InputInterface $input, OutputInterface $output): string
    {
        $defaultBackupDir = realpath($this->projectDir);
        $helper = $this->getHelper('question');
        $question = new Question(sprintf(" <info>In which directory do you want to store the config backup?</>\n <comment>[default: %s]</>\n > ", $defaultBackupDir), $defaultBackupDir);

        $backupDir = $helper->ask($input, $output, $question);
        $output->writeln('');

        return $backupDir;
    }
}
