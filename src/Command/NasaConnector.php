<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:nasa-connect',
    description: 'get data from nasa api',
    hidden: false,
)]

class NasaConnector extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // outputs multiple lines to the console (adding "\n" at the end of each line)
        $output->writeln([
            'Nasa Connector',
            '============',
            '',
        ]);
        return Command::SUCCESS;
    }
}