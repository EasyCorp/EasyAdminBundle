<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Command;

use Doctrine\Common\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Maker\ClassMaker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use function Symfony\Component\String\u;

/**
 * Generates the PHP class needed to define a CRUD controller.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class MakeCrudControllerCommand extends Command
{
    protected static $defaultName = 'make:admin:crud';
    private $classMaker;
    private $doctrine;

    public function __construct(ClassMaker $classMaker, ManagerRegistry $doctrine, string $name = null)
    {
        parent::__construct($name);
        $this->classMaker = $classMaker;
        $this->doctrine = $doctrine;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $doctrineEntitiesFqcn = $this->getAllDoctrineEntitiesFqcn();
        if (0 === \count($doctrineEntitiesFqcn)) {
            $io->error('This command generates the CRUD controller of an existing Doctrine entity, but no entities were found in your application. Create some Doctrine entities first and then run this command again.');

            return 1;
        }
        $entityFqcn = $io->choice(
            'Which Doctrine entity are you going to manage with this CRUD controller?',
            $doctrineEntitiesFqcn
        );
        $entityClassName = u($entityFqcn)->afterLast('\\')->toString();

        $generatedFilePath = $this->classMaker->make(
            // the double '%' in '%%d' is not a mistake
            sprintf('src/Controller/Admin/%s%%dCrudController.php', $entityClassName),
            'crud_controller.tpl',
            ['entity_fqcn' => $entityFqcn, 'entity_class_name' => $entityClassName]
        );

        $io->success('Your CRUD controller class has been successfully generated.');
        $io->text('Next steps:');
        $io->listing([
            sprintf('Configure your controller at "%s"', $generatedFilePath),
            sprintf('Read EasyAdmin docs: https://symfony.com/doc/master/bundles/EasyAdminBundle/index.html'),
        ]);

        return 0;
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
}
