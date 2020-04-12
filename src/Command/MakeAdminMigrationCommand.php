<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Command;

use Doctrine\DBAL\Types\Type;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AvatarField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CodeEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ColorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CountryField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextAreaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use EasyCorp\Bundle\EasyAdminBundle\Maker\CodeBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Maker\Migrator;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use function Symfony\Component\String\u;

/**
 * A command to transform EasyAdmin 2 YAML configuration into the PHP files required by EasyAdmin 3.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class MakeAdminMigrationCommand extends Command
{
    protected static $defaultName = 'make:admin:migration';
    private $migrator;
    private $projectDir;

    public function __construct(Migrator $migrator, string $projectDir, string $name = null)
    {
        parent::__construct($name);
        $this->migrator = $migrator;
        $this->projectDir = $projectDir;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $fs = new Filesystem();

        $io->title('Migration from EasyAdmin2 to EasyAdmin 3');

        $io->text('<info>Step 1.</info> Find the file with the EasyAdmin 2 config backup.');
        $ea2ConfigPath = $this->projectDir.'/easyadmin-config.backup';
        if (file_exists($ea2ConfigPath)) {
            $io->text(sprintf('<info>✅ OK</> The backup file was found at "%s".', $ea2ConfigPath));
        } else {
            $io->text('<error> ERROR </> The backup file was not found. To generate this file, run the <comment>make:admin:migration</comment> command in your application where EasyAdmin 2 is used.');
        }
        $ea2Config = unserialize(file_get_contents($ea2ConfigPath));

        $io->newLine(2);

        $io->text('<info>Step 2.</info> Select the directory where the new PHP files will be generated.');
        $io->text('(type the relative path from the project directory)');
        $outputDir = $io->ask('Directory', 'src/Controller/Admin/');
        $outputDir = $this->projectDir.'/'.ltrim($outputDir, '/');
        $fs->mkdir($outputDir);
        if (!$fs->exists($outputDir)) {
            throw new \RuntimeException(sprintf('The "%s" directory does not exist and cannot be created, so the PHP files cannot be generated.', $outputDir));
        }

        $io->newLine(1);

        $io->text('<info>Step 3.</info> Define the PHP namespace of the new PHP files that will be generated.');
        $namespace = $io->ask('Namespace', 'App\\Controller\\Admin');

        $this->migrator->migrate($ea2Config, $outputDir, $namespace, $io);

        $relativeOutputDir = u($outputDir)->after($this->projectDir)->trimStart('/')->ensureEnd('/')->toString();
        $io->success(sprintf('The migration completed successfully. You can find the generated files at "%s".', $relativeOutputDir));

        return 0;
    }
}
