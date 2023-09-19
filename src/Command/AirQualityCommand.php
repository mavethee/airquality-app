<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Psr\Log\LoggerInterface; // Import the LoggerInterface

#[AsCommand(
    name: 'air-quality',
    description: 'Retrieve and display current and future air quality for a specified location'
)]
class AirQualityCommand extends Command
{
    private $httpClient;
    private $historyFile = 'history.json';
    private $logger;

    public function __construct(HttpClientInterface $httpClient, LoggerInterface $logger = null)
    {
        parent::__construct();
        $this->httpClient = $httpClient;
        $this->logger = $logger; // Inject the logger
    }

    protected function configure(): void
    {
        $this
            ->addArgument('location', InputArgument::OPTIONAL, 'The location to fetch air quality information')
            ->addOption('history', 'H', InputOption::VALUE_NONE, 'Retrieve historical air quality data')
            ->setDescription('Fetches and displays the current air quality for today and the future air quality for tomorrow as 24 hours from now.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($input->getOption('history')) {
            $location = $input->getArgument('location');
            $historicalData = $this->getHistoricalData($location);
            $output->writeln(json_encode($historicalData, JSON_PRETTY_PRINT));
            return Command::SUCCESS;
        }

        // Check if Symfony server is running
        $serverStatusProcess = new Process(['symfony', 'server:status']);
        $serverStatusProcess->run();

        if (!$serverStatusProcess->isSuccessful()) {
            // Start Symfony server if it's not running
            $startServerProcess = new Process(['symfony', 'server:start']);
            $startServerProcess->start();
            sleep(2); // Wait for server to start
        }

        // Get the Weather API key from environment variables
        $weatherApiKey = $_ENV['WEATHER_API_KEY'];

        // Build the URL for current air quality data
        $location = $input->getArgument('location');
        $currentUrl = sprintf('%s?key=%s&q=%s&aqi=yes', 'https://api.weatherapi.com/v1/current.json', $weatherApiKey, $location);

        // Log the API request URL
        if ($this->logger) {
            $this->logger->info('API Request URL for current air quality: ' . $currentUrl);
        }

        // Fetch and display current air quality data
        try {
            $currentResponse = $this->httpClient->request('GET', $currentUrl);
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Failed to make API request for current air quality: ' . $e->getMessage());
            }
            $output->writeln('Failed to retrieve current air quality data.');
            return Command::FAILURE;
        }

        // Log the API response content
        if ($this->logger) {
            $this->logger->info('API Response Content for current air quality: ' . $currentResponse->getContent(false));
        }

        if ($currentResponse->getStatusCode() !== 200) {
            if ($this->logger) {
                $this->logger->error('Failed to retrieve current air quality data. HTTP Status Code: ' . $currentResponse->getStatusCode());
            }
            $output->writeln('Failed to retrieve current air quality data. HTTP Status Code: ' . $currentResponse->getStatusCode());
            return Command::FAILURE;
        }

        $currentData = $currentResponse->toArray();
        $currentEmoji = '';
        $currentColor = '';

        // Determine emoji and color based on PM2.5 value
        if (isset($currentData['current']['air_quality']['pm2_5'])) {
            $currentPm25Index = (float) $currentData['current']['air_quality']['pm2_5'];

            if ($currentPm25Index >= 0 && $currentPm25Index <= 12.0) {
                $currentEmoji = '😃';
                $currentColor = 'green';
            } elseif ($currentPm25Index >= 12.1 && $currentPm25Index <= 35.4) {
                $currentEmoji = '😐';
                $currentColor = 'yellow';
            } elseif ($currentPm25Index >= 35.5 && $currentPm25Index <= 55.4) {
                $currentEmoji = '😷';
                $currentColor = 'yellow';
            } elseif ($currentPm25Index >= 55.5 && $currentPm25Index <= 150.4) {
                $currentEmoji = '😨';
                $currentColor = 'red';
            } elseif ($currentPm25Index >= 150.5 && $currentPm25Index <= 250.4) {
                $currentEmoji = '😷';
                $currentColor = 'red';
            } elseif ($currentPm25Index >= 250.5) {
                $currentEmoji = '🚫';
                $currentColor = 'red';
            }
        }

        $output->writeln("<fg={$currentColor}>{$currentEmoji} Current Air Quality (PM2.5) for today: " . ($currentEmoji ? $currentPm25Index : 'Data not available') . "</>");

        // Build the URL for future air quality data
        $futureUrl = sprintf(
            '%s?key=%s&q=%s&dt=tomorrow&aqi=yes',
            'https://api.weatherapi.com/v1/forecast.json',
            $weatherApiKey,
            $location
        );

        // Log the API request URL for future air quality
        if ($this->logger) {
            $this->logger->info('API Request URL for future air quality: ' . $futureUrl);
        }

        // Fetch and display future air quality data
        try {
            $futureResponse = $this->httpClient->request('GET', $futureUrl);
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Failed to make API request for future air quality: ' . $e->getMessage());
            }
            $output->writeln('Failed to retrieve future air quality data.');
            return Command::FAILURE;
        }

        if ($futureResponse->getStatusCode() !== 200) {
            if ($this->logger) {
                $this->logger->error('Failed to retrieve future air quality data. HTTP Status Code: ' . $futureResponse->getStatusCode());
            }
            $output->writeln('Failed to retrieve future air quality data.');
            return Command::FAILURE;
        }

        $futureData = $futureResponse->toArray();

        // Check if the 'forecast' key exists and access the future air quality data
        if (isset($futureData['forecast']['forecastday'][0]['hour'][0]['air_quality']['pm2_5'])) {
            $futurePm25Index = (float) $futureData['forecast']['forecastday'][0]['hour'][0]['air_quality']['pm2_5'];

            $output->writeln("<fg={$currentColor}>{$currentEmoji} Future Air Quality (PM2.5) for tomorrow (in next 24h): " . ($currentEmoji ? $futurePm25Index : 'Data not available') . "</>");
        } else {
            $output->writeln('Future air quality data is not available for the specified location.');
        }

        // Log historical air quality data
        $this->logHistoricalData($location, $currentPm25Index);

        return Command::SUCCESS;
    }

    // Get historical air quality data
    private function getHistoricalData(string $location = null): array
    {
        $allHistoricalData = $this->getStoredHistoricalData();

        if ($location !== null) {
            // Filter data for the specified location
            $historicalData = array_filter($allHistoricalData, function ($data) use ($location) {
                return $data['location'] === $location;
            });

            return $historicalData;
        }

        return $allHistoricalData;
    }

    // Get all stored historical air quality data
    private function getStoredHistoricalData(): array
    {
        if (file_exists($this->historyFile)) {
            return json_decode(file_get_contents($this->historyFile), true);
        }

        return [];
    }

    // Log historical air quality data
    private function logHistoricalData(string $location, float $pm25Index): void
    {
        $historicalData = $this->getStoredHistoricalData();
        $historicalData[] = [
            'location' => $location,
            'pm25Value' => $pm25Index,
            'date' => (new \DateTime())->format('Y-m-d\TH:i:s\Z'),
        ];

        file_put_contents($this->historyFile, json_encode($historicalData, JSON_PRETTY_PRINT));
    }
}

?>