<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'air-quality',
    description: 'Retrieve and display current and future air quality for a specified location'
)]
class AirQualityCommand extends Command
{
    private $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        parent::__construct();
        $this->httpClient = $httpClient;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('location', InputArgument::REQUIRED, 'The location to fetch air quality information')
            ->setDescription('Fetches and displays the current air quality for today and the future air quality for tomorrow as 24 hours from now.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $location = $input->getArgument('location');

        // Get the Weather API key from environment variables
        $weatherApiKey = $_ENV['WEATHER_API_KEY'];

        // Build the API URL for current air quality (today)
        $currentUrl = sprintf('%s?key=%s&q=%s&aqi=yes', 'https://api.weatherapi.com/v1/current.json', $weatherApiKey, $location);

        // Build the API URL for future air quality (tomorrow at the same hour as now, 24 hours from now)
        $currentHour = date('H');
        $tomorrowSameHour = date('Y-m-d H:i:s', strtotime("+1 day $currentHour:00:00"));

        $futureUrl = sprintf(
            '%s?key=%s&q=%s&dt=%s&aqi=yes',
            'https://api.weatherapi.com/v1/forecast.json',
            $weatherApiKey,
            $location,
            $tomorrowSameHour
        );

        // Retrieve and display current air quality (today)
        $currentResponse = $this->httpClient->request('GET', $currentUrl);

        if ($currentResponse->getStatusCode() !== 200) {
            $output->writeln('Failed to retrieve current air quality data. Please check the location and try again.');
            return Command::FAILURE;
        }

        $currentData = $currentResponse->toArray();
        $currentEmoji = '';
        $currentColor = '';

        if (isset($currentData['current']['air_quality']['pm2_5'])) {
            $currentPm25Index = (float) $currentData['current']['air_quality']['pm2_5'];

            // Determine emoji and color based on PM2.5 value
            if ($currentPm25Index >= 0 && $currentPm25Index <= 12.0) {
                $currentEmoji = '😃';
                $currentColor = 'green';
            } elseif ($currentPm25Index >= 12.1 && $currentPm25Index <= 35.4) {
                $currentEmoji = '😐';
                $currentColor = 'yellow';
            } elseif ($currentPm25Index >= 35.5 && $currentPm25Index <= 55.4) {
                $currentEmoji = '😷';
                $currentColor = 'yellow'; // I ran out of colors and ideas pardon.
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

        // Retrieve and display future air quality (tomorrow at the same hour as now)
        $futureResponse = $this->httpClient->request('GET', $futureUrl);

        if ($futureResponse->getStatusCode() !== 200) {
            $output->writeln('Failed to retrieve future air quality data.');
            return Command::SUCCESS; // Return success even if future data is not available, this was my debugging desperation before future output was working.
        }

        $futureData = $futureResponse->toArray();

        if (!isset($futureData['forecast']['forecastday'][0]['hour'][0]['air_quality']['pm2_5'])) {
            $output->writeln('Future air quality data is not available for the specified location.');
            return Command::SUCCESS; // Return success even if future data is not available
        }

        $futurePm25Index = (float) $futureData['forecast']['forecastday'][0]['hour'][0]['air_quality']['pm2_5'];

        // Display future air quality (tomorrow at the same hour as now)
        $output->writeln("<fg={$currentColor}>{$currentEmoji} Future Air Quality (PM2.5), for tomorrow (in next 24h): " . ($currentEmoji ? $futurePm25Index : 'Data not available') . "</>");

        return Command::SUCCESS;
    }
}

?>