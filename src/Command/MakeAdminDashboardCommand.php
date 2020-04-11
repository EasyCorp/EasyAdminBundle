<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Command;

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
class MakeAdminDashboardCommand extends Command
{
    protected static $defaultName = 'make:admin:dashboard';
    private $projectDir;

    public function __construct(string $projectDir, string $name = null)
    {
        parent::__construct($name);
        $this->projectDir = $projectDir;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $fs = new Filesystem();

        $targetDir = sprintf('%s/src/Controller/Admin', $this->projectDir);
        $fs->mkdir($targetDir);
        if (!$fs->exists($targetDir)) {
            throw new \RuntimeException(sprintf('The "%s" directory does not exist and cannot be created, so it\'s not possible to generate the Dashboard class in that directory', $targetDir));
        }

        $targetFile = 'DashboardController.php';
        $i = 1;
        while ($fs->exists(sprintf('%s/%s', $targetDir, $targetFile))) {
            $targetFile = sprintf('Dashboard%dController.php', ++$i);
        }
        $targetPath = sprintf('%s/%s', $targetDir, $targetFile);

        $skeletonPath = sprintf('%s/Resources/skeleton/dashboard.tpl.php', __DIR__.'/../');
        $skeletonParameters = [
            'class_name' => u($targetFile)->beforeLast('.php'),
            'namespace' => 'App\\Controller\\Admin',
            'site_title' => $this->getSiteTitle($this->projectDir),
        ];

        ob_start();
        extract($skeletonParameters, EXTR_SKIP);
        include $skeletonPath;
        $generatedClassContents = ob_get_clean();

        $fs->dumpFile($targetPath, $generatedClassContents);

        $io->success('Your dashboard class has been successfully generated.');

        $relativePath = u($targetPath)->after($this->projectDir)->trim('/')->toString();
        $io->text('Next steps:');
        $io->listing([
            sprintf('Configure your Dashboard at "%s"', $relativePath),
            sprintf('Run "make:admin:crud" to generate CRUD controllers and link them from the Dashboard.'),
        ]);

        return 0;
    }

    private function getSiteTitle(string $projectDir): string
    {
        $guessedTitle = (new AsciiSlugger())
            ->slug(basename($projectDir), ' ')
            ->title(true)
            ->trim()
            ->toString();

        return empty($guessedTitle) ? 'EasyAdmin' : $guessedTitle;
    }
}
