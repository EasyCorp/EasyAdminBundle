<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Command;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Maker\ClassMaker;
use EasyCorp\Bundle\EasyAdminBundle\Maker\CrudFieldGuesser;
use EasyCorp\Bundle\EasyAdminBundle\Maker\GuessedField;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
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
    public function __construct(private readonly string $projectDir, private readonly ClassMaker $classMaker, private readonly ManagerRegistry $doctrine, ?string $name = null)
    {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->setHelp($this->getCommandHelp())
            ->addArgument('entity_fqcn', InputArgument::OPTIONAL, 'The FQCN of the Doctrine entity managed with this CRUD controller')
            ->addOption('fields', null, InputOption::VALUE_REQUIRED, 'Comma-separated list of the entity properties to show in the CRUD controller (e.g. "id,title,author")')
            ->addOption('no-fields', null, InputOption::VALUE_NONE, 'Generate a commented example of the fields instead of the fields of the entity properties')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $fs = new Filesystem();
        $fieldGuesser = new CrudFieldGuesser();

        if (null !== $input->getOption('fields') && true === $input->getOption('no-fields')) {
            $io->error('The "--fields" and "--no-fields" options can\'t be used at the same time. Use only one of them.');

            return Command::FAILURE;
        }

        $doctrineEntitiesFqcn = $this->getAllDoctrineEntitiesFqcn();
        if (0 === \count($doctrineEntitiesFqcn)) {
            $io->error('This command generates the CRUD controller of an existing Doctrine entity, but no entities were found in your application. Create some Doctrine entities first and then run this command again.');

            return Command::FAILURE;
        }

        if (null !== $input->getArgument('entity_fqcn')) {
            $entityFqcn = $input->getArgument('entity_fqcn');
            if (!\in_array($entityFqcn, $doctrineEntitiesFqcn, true)) {
                $io->error(sprintf('The "%s" entity does not exist. Pick one of the existing entities: %s', $entityFqcn, implode(', ', $doctrineEntitiesFqcn)));

                return Command::FAILURE;
            }
        } elseif (!$input->isInteractive()) {
            $io->error(sprintf('Pass the FQCN of the Doctrine entity as the first argument of this command when running it in non-interactive mode. Pick one of the existing entities: %s', implode(', ', $doctrineEntitiesFqcn)));

            return Command::FAILURE;
        } else {
            $entityFqcn = $io->choice(
                'Which Doctrine entity are you going to manage with this CRUD controller?',
                $doctrineEntitiesFqcn
            );
        }

        $entityMetadata = $this->getEntityMetadata($entityFqcn);
        $guessedFields = $fieldGuesser->guess($entityMetadata);

        if (true === $input->getOption('no-fields')) {
            $fieldPropertyNames = [];
        } elseif (null !== $input->getOption('fields')) {
            $fieldPropertyNames = $this->parseFieldsOption($input->getOption('fields'));
            if ([] === $fieldPropertyNames) {
                $io->error('The "--fields" option doesn\'t include any property name. Pass some property names (e.g. --fields=id,title) or use the "--no-fields" option.');

                return Command::FAILURE;
            }

            $unknownPropertyNames = array_diff($fieldPropertyNames, array_keys($guessedFields));
            if ([] !== $unknownPropertyNames) {
                $io->error(sprintf('The "%s" entity doesn\'t have these properties or this command doesn\'t support their Doctrine types: %s. Pick some of these properties: %s.', $entityFqcn, implode(', ', $unknownPropertyNames), implode(', ', array_keys($guessedFields))));

                return Command::FAILURE;
            }
        } else {
            $fieldPropertyNames = $this->askFieldPropertyNames($io, $guessedFields);
        }

        $fields = $fieldGuesser->limitIndexColumns(array_map(
            static fn (string $propertyName): GuessedField => $guessedFields[$propertyName],
            $fieldPropertyNames
        ));

        $entityClassName = u($entityFqcn)->afterLast('\\')->toString();
        $controllerFileNamePattern = sprintf('%s{number}CrudController.php', $entityClassName);

        $projectDir = $this->projectDir;
        $controllerDir = $io->ask('Which directory do you want to generate the CRUD controller in?', 'src/Controller/Admin/', static function (string $selectedDir) use ($fs, $projectDir) {
            $absoluteDir = u($selectedDir)->ensureStart($projectDir.\DIRECTORY_SEPARATOR);
            if (null !== $absoluteDir->indexOf('..')) {
                throw new \RuntimeException(sprintf('The given directory path can\'t contain ".." and must be relative to the project directory (which is "%s")', $projectDir));
            }

            $fs->mkdir($absoluteDir);

            if (!$fs->exists($absoluteDir)) {
                throw new \RuntimeException('The given directory does not exist and couldn\'t be created. Type in the path of an existing directory relative to your project root (e.g. src/Controller/Admin/)');
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

        // the commented example of configureFields() uses these fields
        $fieldFqcns = [] === $fields
            ? [IdField::class, TextEditorField::class, TextField::class]
            : [FormField::class, ...array_map(static fn (GuessedField $field): string => $field->fieldFqcn, $fields)];
        $importedFqcns = array_unique([AbstractCrudController::class, ...$fieldFqcns]);
        $importedClassNames = array_map(static fn (string $fqcn): string => u($fqcn)->afterLast('\\')->toString(), $importedFqcns);
        $entityAlias = \in_array($entityClassName, $importedClassNames, true) ? $entityClassName.'Entity' : $entityClassName;
        $uses = [...$importedFqcns, $entityAlias === $entityClassName ? $entityFqcn : sprintf('%s as %s', $entityFqcn, $entityAlias)];
        sort($uses, \SORT_STRING | \SORT_FLAG_CASE);

        $generatedFilePath = $this->classMaker->make(
            sprintf('%s/%s', $controllerDir, $controllerFileNamePattern),
            'crud_controller.tpl',
            [
                'namespace' => $namespace,
                'uses' => $uses,
                'entity_class_name' => $entityAlias,
                'fields' => array_map(static fn (GuessedField $field): string => $field->toPhp(), $fields),
            ]
        );

        $io->success('Your CRUD controller class has been successfully generated.');

        $unsupportedPropertyNames = array_diff($entityMetadata->getFieldNames(), array_keys($guessedFields));
        if ([] !== $fields && [] !== $unsupportedPropertyNames) {
            $io->note(sprintf('These properties were left out because this command doesn\'t know which field to use for their Doctrine types: %s. Add them by hand if you need them.', implode(', ', $unsupportedPropertyNames)));
        }

        $io->text('Next steps:');
        $io->listing([
            sprintf('Configure your controller at "%s"', $generatedFilePath),
            'Read EasyAdmin docs: https://symfony.com/doc/master/bundles/EasyAdminBundle/index.html',
        ]);

        return Command::SUCCESS;
    }

    /**
     * @param array<string, GuessedField> $guessedFields
     *
     * @return list<string>
     */
    private function askFieldPropertyNames(SymfonyStyle $io, array $guessedFields): array
    {
        $choices = [
            'all' => 'All the suggested properties (default)',
            'none' => 'None: skip this and keep a commented example',
        ];
        foreach ($guessedFields as $propertyName => $guessedField) {
            // properties called "all" or "none" can't override these special answers, but they can be selected with the --fields option
            $choices[$propertyName] ??= $guessedField->describe();
        }

        $question = new ChoiceQuestion('Which properties do you want to show in this CRUD controller?', $choices, 'all');
        $question->setMultiselect(true);
        $choicesValidator = $question->getValidator();
        $question->setValidator(static function (?string $answer) use ($choicesValidator): array {
            $selectedChoices = $choicesValidator($answer);
            if (\count($selectedChoices) > 1 && [] !== array_intersect($selectedChoices, ['all', 'none'])) {
                throw new \InvalidArgumentException('The "all" and "none" answers can\'t be combined with property names.');
            }

            return $selectedChoices;
        });

        $selectedChoices = $io->askQuestion($question);

        return match ($selectedChoices) {
            ['all'] => array_keys(array_filter($guessedFields, static fn (GuessedField $field): bool => $field->suggested)),
            ['none'] => [],
            default => array_values(array_unique($selectedChoices)),
        };
    }

    /**
     * @return list<string>
     */
    private function parseFieldsOption(string $fieldsOption): array
    {
        $propertyNames = array_map(trim(...), explode(',', $fieldsOption));

        return array_values(array_unique(array_filter($propertyNames, static fn (string $propertyName): bool => '' !== $propertyName)));
    }

    /**
     * @return array<class-string>
     */
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

    /**
     * @param class-string $entityFqcn
     *
     * @return ClassMetadata<object>
     */
    private function getEntityMetadata(string $entityFqcn): ClassMetadata
    {
        $entityMetadata = $this->doctrine->getManagerForClass($entityFqcn)?->getClassMetadata($entityFqcn);
        if (!$entityMetadata instanceof ClassMetadata) {
            throw new \RuntimeException(sprintf('The "%s" class is not managed by Doctrine ORM, so this command can\'t generate its CRUD controller.', $entityFqcn));
        }

        return $entityMetadata;
    }

    private function getCommandHelp(): string
    {
        return <<<'HELP'
            The <info>%command.name%</info> command creates a new EasyAdmin CRUD controller
            class to manage some Doctrine entity in your application.

            Follow the steps shown by the command to select the Doctrine entity, the
            properties to show in the controller and the location and namespace of the
            generated class.

            The command reads the Doctrine metadata of the entity to generate the
            <comment>configureFields()</comment> method with the field that fits each property.
            Use the <info>--fields</info> option to pick the properties without being asked
            (they are added in the given order) or the <info>--no-fields</info> option to
            generate a commented example instead:

              <info>php %command.full_name% "App\Entity\Product" --fields=id,title,author</info>
              <info>php %command.full_name% "App\Entity\Product" --no-fields</info>

            This command never changes or overwrites an existing class, so you can run it
            safely as many times as needed to create multiple CRUD controllers.
            HELP;
    }
}
