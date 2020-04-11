<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Command;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use function Symfony\Component\String\u;

/**
 * Generates the PHP class needed to define a CRUD controller.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class MakeCrudControllerCommand extends Command
{
    protected static $defaultName = 'make:admin:crud';
    private $projectDir;
    private $doctrine;

    public function __construct(string $projectDir, Registry $doctrine, string $name = null)
    {
        parent::__construct($name);
        $this->projectDir = $projectDir;
        $this->doctrine = $doctrine;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $fs = new Filesystem();

        $entityFqcn = $io->choice(
            'Which Doctrine entity are you going to manage with this CRUD controller?',
            $this->getAllDoctrineEntitiesFqcn()
        );

        $targetDir = sprintf('%s/src/Controller/Admin', $this->projectDir);
        $fs->mkdir($targetDir);
        if (!$fs->exists($targetDir)) {
            throw new \RuntimeException(sprintf('The "%s" directory does not exist and cannot be created, so it\'s not possible to generate the Dashboard class in that directory', $targetDir));
        }

        $entityClassName = u($entityFqcn)->afterLast('\\')->toString();
        $targetFile = sprintf('%sCrudController.php', $entityClassName);
        $i = 1;
        while ($fs->exists(sprintf('%s/%s', $targetDir, $targetFile))) {
            $targetFile = sprintf('%s%dCrudController.php', $entityClassName, ++$i);
        }
        $targetPath = sprintf('%s/%s', $targetDir, $targetFile);

        $skeletonPath = sprintf('%s/Resources/skeleton/crud_controller.tpl.php', __DIR__.'/../');
        $skeletonParameters = [
            'entity_fqcn' => $entityFqcn,
            'entity_class_name' => $entityClassName,
            'crud_controller_class_name' => u($targetFile)->beforeLast('.php'),
            'crud_controller_namespace' => 'App\\Controller\\Admin',
        ];

        ob_start();
        extract($skeletonParameters, EXTR_SKIP);
        include $skeletonPath;
        $generatedClassContents = ob_get_clean();

        $fs->dumpFile($targetPath, $generatedClassContents);

        $io->success('Your CRUD controller class has been successfully generated.');

        $relativePath = u($targetPath)->after($this->projectDir)->trim('/')->toString();
        $io->text('Next steps:');
        $io->listing([
            sprintf('Configure your controller at "%s"', $relativePath),
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
