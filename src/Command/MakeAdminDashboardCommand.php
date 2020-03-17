<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Generates the PHP class needed to define a backend dashboard.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class MakeAdminDashboardCommand extends Command
{
    protected static $defaultName = 'make:admin:dashboard';

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->error('This command is not implemented.');

        return 0;
    }
}
