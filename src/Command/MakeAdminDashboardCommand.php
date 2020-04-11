<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Command;

use EasyCorp\Bundle\EasyAdminBundle\Maker\ClassMaker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\String\Slugger\AsciiSlugger;

/**
 * Generates the PHP class needed to define a Dashboard controller.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class MakeAdminDashboardCommand extends Command
{
    protected static $defaultName = 'make:admin:dashboard';
    private $classMaker;
    private $projectDir;

    public function __construct(ClassMaker $classMaker, string $projectDir, string $name = null)
    {
        parent::__construct($name);
        $this->classMaker = $classMaker;
        $this->projectDir = $projectDir;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $generatedFilePath = $this->classMaker->make('src/Controller/Admin/Dashboard%dController.php', 'dashboard.tpl', ['site_title' => $this->getSiteTitle($this->projectDir)]);

        $io = new SymfonyStyle($input, $output);
        $io->success('Your dashboard class has been successfully generated.');
        $io->text('Next steps:');
        $io->listing([
            sprintf('Configure your Dashboard at "%s"', $generatedFilePath),
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
