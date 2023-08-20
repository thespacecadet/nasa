<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

#[AsCommand(
    name: 'app:nasa-connect',
    description: 'get data from nasa api',
    hidden: false,
)]
class NasaConnector extends Command
{
    private $fileSystem;

    public function __construct()
    {
        parent::__construct();
        $this->fileSystem = new Filesystem();
    }

    protected function configure(): void
    {
        $this
            // ...
            ->addArgument('target_folder', InputArgument::REQUIRED, 'Where would you like to save the data?')
            ->addArgument('date', InputArgument::OPTIONAL, 'Which date?');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $date = $input->getArgument('date')?$input->getArgument('date'):'default';
        $text = 'Target folder is ' . $input->getArgument('target_folder');
        $output->writeln([
            'Nasa Connector',
            '============',
            '',
            $text,
        ]);

//        $fsObject = new Filesystem();
        $current_dir_path = getcwd();
        $baseFolder = $current_dir_path . "/public";
        $output->writeln([
            'base path:',
            $baseFolder,
        ]);
        $output->writeln([
            $this->fileSystem->exists($baseFolder . "/nasaImages"),
            $baseFolder . "/nasaImages"
        ]);

        //create base directory
        if (!$this->fileSystem->exists($baseFolder . "/nasaImages")) {
            $output->writeln('nasaImages folder does not exist! creating one for you...');
            $this->fileSystem->mkdir($baseFolder . "/nasaImages");
            $output->writeln('base Folder created');
        } else {
            $output->writeln('base Folder already exists');
        }
        $output->writeln(['Creating new date specific folder, if one does not exist']);
        if (!$this->fileSystem->exists($baseFolder . "/nasaImages/" . $date)) {
            $output->writeln('Date folder does not exist! creating one for you...');
            $this->fileSystem->mkdir($baseFolder . "/nasaImages/" . $date);
            $output->writeln('Date Folder created');
        }
        else { // if date folder exists, remove it for now

            $output->writeln('Date Folder exists deleting it');
            $this->fileSystem->remove($baseFolder . "/nasaImages/" . $date);
            $output->writeln('Date Folder removed');
        }
//        $output->writeln('current date is ' . $date);

        return Command::SUCCESS;
    }


}