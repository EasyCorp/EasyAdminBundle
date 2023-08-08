<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Command;

use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Maker\ClassMaker;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use function Symfony\Component\String\u;

/**
 * Generates the PHP class needed to define a CRUD controller.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
#[AsCommand(
    name: 'make:admin:crud',
    description: 'Creates a new EasyAdmin CRUD controller class',
)]
class MakeCrudControllerCommand extends Command
{
    private string $projectDir;
    private ClassMaker $classMaker;
    private ManagerRegistry $doctrine;

    public function __construct(string $projectDir, ClassMaker $classMaker, ManagerRegistry $doctrine, ?string $name = null)
    {
        parent::__construct($name);
        $this->projectDir = $projectDir;
        $this->classMaker = $classMaker;
        $this->doctrine = $doctrine;
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

        $doctrineEntitiesFqcn = $this->getAllDoctrineEntitiesFqcn();
        if (0 === \count($doctrineEntitiesFqcn)) {
            $io->error('This command generates the CRUD controller of an existing Doctrine entity, but no entities were found in your application. Create some Doctrine entities first and then run this command again.');

            return Command::FAILURE;
        }
        $entityFqcn = $io->choice(
            'Which Doctrine entity are you going to manage with this CRUD controller?',
            $doctrineEntitiesFqcn
        );
        $entityClassName = u($entityFqcn)->afterLast('\\')->toString();
        $controllerFileNamePattern = sprintf('%s{number}CrudController.php', $entityClassName);

        $projectDir = $this->projectDir;
        $controllerDir = $io->ask('Which directory do you want to generate the CRUD controller in?', 'src/Controller/Admin/', static function (string $selectedDir) use ($fs, $projectDir) {
            $absoluteDir = u($selectedDir)->ensureStart($projectDir.\DIRECTORY_SEPARATOR);
            if (!$fs->exists($absoluteDir)) {
                throw new \RuntimeException('The given directory does not exist. Type in the path of an existing directory relative to your project root (e.g. src/Controller/Admin/)');
            }

            return $absoluteDir->after($projectDir.\DIRECTORY_SEPARATOR)->trimEnd(\DIRECTORY_SEPARATOR)->toString();
        });

        $guessedNamespace = u($controllerDir)->equalsTo('src')
            ? 'App'
            : u($controllerDir)->replace('/', ' ')->replace('\\', ' ')->replace('src ', 'app ')->title(true)->replace('App App ', 'App ')->replace(' ', '\\')->trimEnd(\DIRECTORY_SEPARATOR);
        $namespace = $io->ask(
            'Namespace of the generated CRUD controller',
            $guessedNamespace,
            static fn (string $namespace): string => u($namespace)->replace('/', '\\')->toString()
        );

        $generatedFilePath = $this->classMaker->make(
            sprintf('%s/%s', $controllerDir, $controllerFileNamePattern),
            'crud_controller.tpl',
            ['entity_fqcn' => $entityFqcn, 'entity_class_name' => $entityClassName, 'namespace' => $namespace]
        );

        $io->success('Your CRUD controller class has been successfully generated.');
        $io->text('Next steps:');
        $io->listing([
            sprintf('Configure your controller at "%s"', $generatedFilePath),
            'Read EasyAdmin docs: https://symfony.com/doc/master/bundles/EasyAdminBundle/index.html',
        ]);

        return Command::SUCCESS;
    }

    private function getAllDoctrineEntitiesFqcn(): array
    {
        $entitiesFqcn = [];
        foreach ($this->doctrine->getManagers() as $entityManager) {
            $classesMetadata = $entityManager->getMetadataFactory()->getAllMetadata();
            foreach ($classesMetadata as $classMetadata) {
                $entitiesFqcn[] = $classMetadata->getName();
            }
        }

        sort($entitiesFqcn);

        return $entitiesFqcn;
    }

    private function getCommandHelp(): string
    {
        return <<<'HELP'
            The <info>%command.name%</info> command creates a new EasyAdmin CRUD controler
            class to manage some Doctrine entity in your application.

            Follow the steps shown by the command to select the Doctrine entity and the
            location and namespace of the generated class.

            This command never changes or overwrites an existing class, so you can run it
            safely as many times as needed to create multiple CRUD controllers.
            HELP;
    }
}
