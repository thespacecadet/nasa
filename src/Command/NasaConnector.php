<?php

namespace App\Command;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(
    name: 'app:nasa-connect',
    description: 'get data from nasa api',
    hidden: false,
)]
class NasaConnector extends Command
{
    /**
     * @var Filesystem
     */
    private Filesystem $fileSystem;

    /**
     * @var Client
     */
    private Client $client;

    /**
     * @var string
     */
    private string $apiKey = 'p960B4skMQHGdPnetw2KYFVzzoomz4GV5oZMZjUM';

    /**
     * @var array[]
     */
    private array $params;

    public function __construct()
    {
        parent::__construct();
        $this->params = [
            'query' => [
                'api_key' => $this->apiKey
            ]
        ];
        $this->client = new Client(['base_uri' => 'https://api.nasa.gov']);
        $this->fileSystem = new Filesystem();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('target_folder', InputArgument::REQUIRED, 'Where would you like to save the data?')
            ->addArgument('date', InputArgument::OPTIONAL, 'Which date?');
    }

    /**
     * execute command
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        //get date from arguments or find the last date through the nasa api
        $date = $input->getArgument('date') ? $input->getArgument('date') : $this->getLatestDate();
        $targetFolder = $input->getArgument('target_folder');
        $baseFolder = getcwd() . "/public/" . $targetFolder;

        $output->writeln([
            'writing to folder:',
            $baseFolder,
        ]);

        //create target directory if it is not already there
        if (!$this->fileSystem->exists($baseFolder)) {
            $output->writeln('target folder does not exist! creating one for you...');
            $this->fileSystem->mkdir($baseFolder);
        }

        //create date folder. if it exists, remove it and create it again (for testing purposes)
        $output->writeln(['Creating new date specific folder, if one does not exist']);
        $dateFolder = $baseFolder . "/" . $date;
        if (!$this->fileSystem->exists($dateFolder)) {
            $output->writeln('Date folder does not exist! creating one for you...');
            $this->fileSystem->mkdir($dateFolder);
            $output->writeln('Date Folder created');
        } else {
            $output->writeln('Date Folder already exists');
            $this->fileSystem->remove($dateFolder);
            $this->fileSystem->mkdir($dateFolder);
            $output->writeln('Date Folder removed and re-added');
        }

        //get images metadata for the given date
        $dailyData = $this->getDailyData($date);

        // Save all images from the given date in the specified folder
        $this->getImagesFromDate($dailyData, $dateFolder, $date);

        return Command::SUCCESS;
    }

    /**
     * get metadata for images for given date
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getDailyData($date)
    {
        try {
            $request = $this->client->get('/EPIC/api/natural/date/' . $date, $this->params);
        } catch (RequestException $e) {
            echo 'error importing daily data: ' . $e->getMessage();
            return [];
        }
        return json_decode($request->getBody());
    }

    /**
     * find the latest date available
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function getLatestDate(): string
    {
        try {
            $request = $this->client->get('/EPIC/api/natural/all', $this->params);
        } catch (RequestException $e) {
            echo $e;
        }
        $result = json_decode($request->getBody());
        return $result[0]->date; //return the first result since this array is sorted by date
    }

    /**
     * save all images from a given date
     * @param string $date
     * @return void
     */
    private function getImagesFromDate(mixed $dailyData, string $folder, string $date)
    {
        //reformat date for the use of the api
        $year = substr($date, 0, 4);
        $month = substr($date, 5, 2);
        $day = substr($date, -2);

        //base api url
        $baseUrl = 'https://epic.gsfc.nasa.gov/archive/natural/' . $year . '/' . $month . '/' . $day . '/png/';

        //for each image save a png copy locally
        foreach ($dailyData as $image) {
            try {
                $imageName = $image->image . '.png';
                $imageURL = $baseUrl . $imageName;
                copy($imageURL, $folder . '/' . $imageName);
                echo 'download image' . $imageName . "\n";
            } catch (RequestException $e) {
                echo $e;
            }
        }
    }
}