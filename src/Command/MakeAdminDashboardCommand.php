<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Command;

use EasyCorp\Bundle\EasyAdminBundle\Maker\ClassMaker;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\String\Slugger\AsciiSlugger;
use function Symfony\Component\String\u;

/**
 * Generates the PHP class needed to define a Dashboard controller.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
#[AsCommand(
    name: 'make:admin:dashboard',
    description: 'Creates a new EasyAdmin Dashboard class',
)]
class MakeAdminDashboardCommand extends Command
{
    private ClassMaker $classMaker;
    private string $projectDir;

    public function __construct(ClassMaker $classMaker, string $projectDir, ?string $name = null)
    {
        parent::__construct($name);
        $this->classMaker = $classMaker;
        $this->projectDir = $projectDir;
    }

    protected function configure(): void
    {
        $this
            ->setHelp($this->getCommandHelp())
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $fs = new Filesystem();

        $controllerClassName = $io->ask(
            'Which class name do you prefer for your Dashboard controller?',
            'DashboardController',
            fn (string $className): string => u($className)->ensureEnd('Controller')->toString()
        );

        $projectDir = $this->projectDir;
        $controllerDir = $io->ask(
            sprintf('In which directory of your project do you want to generate "%s"?', $controllerClassName),
            'src/Controller/Admin/',
            static function (string $selectedDir) use ($fs, $projectDir) {
                $absoluteDir = u($selectedDir)->ensureStart($projectDir.\DIRECTORY_SEPARATOR);
                if (null !== $absoluteDir->indexOf('..')) {
                    throw new \RuntimeException(sprintf('The given directory path can\'t contain ".." and must be relative to the project directory (which is "%s")', $projectDir));
                }

                $fs->mkdir($absoluteDir);

                if (!$fs->exists($absoluteDir)) {
                    throw new \RuntimeException('The given directory does not exist and couldn\'t be created. Type in the path of an existing directory relative to your project root (e.g. src/Controller/Admin/)');
                }

                return $absoluteDir->after($projectDir.\DIRECTORY_SEPARATOR)->trimEnd(\DIRECTORY_SEPARATOR)->toString();
            }
        );

        $controllerFilePath = sprintf('%s/%s.php', u($controllerDir)->ensureStart($projectDir.\DIRECTORY_SEPARATOR), $controllerClassName);
        if ($fs->exists($controllerFilePath)) {
            throw new \RuntimeException(sprintf('The "%s.php" file already exists in the given "%s" directory. Use a different controller name or generate it in a different directory.', $controllerClassName, $controllerDir));
        }

        $guessedNamespace = u($controllerDir)->equalsTo('src')
            ? 'App'
            : u($controllerDir)->replace('/', ' ')->replace('\\', ' ')->replace('src ', 'app ')->title(true)->replace(' ', '\\')->trimEnd('\\');

        $generatedFilePath = $this->classMaker->make(sprintf('%s/%s.php', $controllerDir, $controllerClassName), 'dashboard.tpl', [
            'namespace' => $guessedNamespace,
            'site_title' => $this->getSiteTitle($this->projectDir),
        ]);

        $io = new SymfonyStyle($input, $output);
        $io->success('Your dashboard class has been successfully generated.');
        $io->text('Next steps:');
        $io->listing([
            sprintf('Configure your Dashboard at "%s"', $generatedFilePath),
            'Run "make:admin:crud" to generate CRUD controllers and link them from the Dashboard.',
        ]);

        return Command::SUCCESS;
    }

    private function getSiteTitle(string $projectDir): string
    {
        $guessedTitle = (new AsciiSlugger())
            ->slug(basename($projectDir), ' ')
            ->title(true)
            ->trim()
            ->toString();

        return '' === $guessedTitle ? 'EasyAdmin' : $guessedTitle;
    }

    private function getCommandHelp(): string
    {
        return <<<'HELP'
            The <info>%command.name%</info> command creates a new EasyAdmin Dashboard class
            in your application. Follow the steps shown by the command to configure the
            name and location of the new class.

            This command never changes or overwrites an existing class, so you can run it
            safely as many times as needed to create multiple dashboards.
            HELP;
    }
}
