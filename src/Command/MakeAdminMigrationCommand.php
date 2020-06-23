<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Command;

use EasyCorp\Bundle\EasyAdminBundle\Maker\Migrator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Terminal;
use Symfony\Component\Filesystem\Filesystem;

/**
 * A command to transform EasyAdmin 2 YAML configuration into the PHP files required by EasyAdmin 3.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class MakeAdminMigrationCommand extends Command
{
    protected static $defaultName = 'make:admin:migration';

    public const SUCCESS = 0;
    public const FAILURE = 1;

    private $migrator;
    private $projectDir;
    /** @var InputInterface */
    private $input;
    /** @var ConsoleSectionOutput */
    private $progressSection;
    private $progressSectionLines = [];
    /** @var ConsoleSectionOutput */
    private $temporarySection;

    public function __construct(Migrator $migrator, string $projectDir, string $name = null)
    {
        parent::__construct($name);
        $this->migrator = $migrator;
        $this->projectDir = $projectDir;
    }

    public function configure()
    {
        $this
            ->addArgument('ea2-backup-file', InputArgument::OPTIONAL, 'The path to the EasyAdmin 2 backup file you want to migrate from.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $fs = new Filesystem();

        $this->input = $input;
        $this->progressSection = $output->section();
        $this->temporarySection = $output->section();

        $this->clearScreen($output);
        $io->title('Migration from EasyAdmin2 to EasyAdmin 3');

        $this->addStep('<info>Step 1/3.</info> Find the file with the EasyAdmin 2 config backup.');
        $ea2ConfigBackupPath = $input->getArgument('ea2-backup-file') ?: $this->projectDir.'/easyadmin-config.backup';

        if (!$fs->exists($ea2ConfigBackupPath)) {
            $this->temporarySection->write(sprintf(
            '<error> ERROR </error> The config backup file was not found in %s. To generate this file, run the <comment>make:admin:migration</comment> command in your application BEFORE upgrading to EasyAdmin 3 (the command must be run while still using EasyAdmin 2).',
            $ea2ConfigBackupPath
            ));
        }
        while (!$fs->exists($ea2ConfigBackupPath)) {
            $ea2ConfigBackupPath = $this->askQuestion('Absolute path of <comment>easyadmin-config.backup</comment> file:');
        }
        $this->temporarySection->write(sprintf('<bg=green;fg=black> OK </> The backup file was found at "%s"', $ea2ConfigBackupPath));
        $ea2Config = unserialize(file_get_contents($ea2ConfigBackupPath), ['allowed_classes' => false]);

        $this->askQuestion('Press <comment>\<Enter></comment> to continue...');
        $this->addStep(sprintf('<bg=green;fg=black> OK </> The backup file was found at "%s"', $ea2ConfigBackupPath));
        $this->temporarySection->clear();

        $this->addStep('');
        $this->addStep('<info>Step 2/3.</info> Select the directory where the new PHP files will be generated.');

        $this->temporarySection->write(sprintf('Type the relative path from your project directory, which is: %s', $this->projectDir));
        $relativeOutputDir = $this->askQuestion('Directory [<comment>src/Controller/Admin/</comment>]:', 'src/Controller/Admin');

        $outputDir = $this->projectDir.'/'.ltrim($relativeOutputDir, '/');
        $fs->mkdir($outputDir);
        if (!$fs->exists($outputDir)) {
            $this->temporarySection->clear();
            $this->temporarySection->write(sprintf('<error> ERROR </error> The "%s" directory does not exist and cannot be created, so the PHP files cannot be generated.', $outputDir));

            return self::FAILURE;
        }
        $this->addStep(sprintf('<bg=green;fg=black> OK </> Output dir = "%s"', $outputDir));
        $this->temporarySection->clear();

        $this->addStep('');
        $this->addStep('<info>Step 3/3.</info> Define the namespace of the new PHP files that will be generated.');
        $namespace = $this->askQuestion('Namespace [<comment>App\\Controller\\Admin</comment>]:', 'App\\Controller\\Admin');

        $namespace = str_replace('/', '\\', $namespace);
        $this->addStep(sprintf('<bg=green;fg=black> OK </> Namespace = "%s"', $namespace));
        $this->temporarySection->clear();

        $this->migrator->migrate($ea2Config, $outputDir, $namespace, $this->temporarySection);

        $this->temporarySection->write('');
        $io->success(sprintf('The migration completed successfully. You can find the generated files at "%s".', $relativeOutputDir));

        return self::SUCCESS;
    }

    private function clearScreen(OutputInterface $output): void
    {
        // clears the entire screen
        $output->write("\x1b[2J");
        // moves cursor to top left position
        $output->write("\x1b[1;1H");
    }

    private function addStep(string $newLine): void
    {
        $this->progressSectionLines[] = $newLine;
        $this->progressSection->clear();

        $terminal = new Terminal();

        foreach ($this->progressSectionLines as $line) {
            $this->progressSection->write($line);
        }

        $this->progressSection->write('');
        $this->progressSection->write(str_repeat('━', $terminal->getWidth()));
        $this->progressSection->write('');
    }

    private function askQuestion(string $questionText, $defaultAnswer = null)
    {
        $helper = $this->getHelper('question');
        $question = new Question($questionText, $defaultAnswer);

        return $helper->ask($this->input, $this->temporarySection, $question);
    }
}
