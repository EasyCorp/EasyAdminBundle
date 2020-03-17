<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * A command to transform EasyAdmin 2 YAML configuration into the PHP files required by EasyAdmin 3.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class MakeAdminMigrationCommand extends Command
{
    protected static $defaultName = 'make:admin:migration';

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->error('This command is not implemented.');

        return 0;
    }
}
