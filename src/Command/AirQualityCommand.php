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
    description: 'Retrieve and display current air quality for a specified location'
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
            ->setDescription('Fetches and displays the current air quality for a specified location');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $location = $input->getArgument('location');

        // Get the Weather API key from environment variables
        $weatherApiKey = $_ENV['WEATHER_API_KEY'];

        // Build the API URL
        $url = sprintf('%s?key=%s&q=%s&aqi=yes', 'https://api.weatherapi.com/v1/current.json', $weatherApiKey, $location);

        $response = $this->httpClient->request('GET', $url);

        if ($response->getStatusCode() !== 200) {
            $output->writeln('Failed to retrieve air quality data. Please check the location and try again.');
            return Command::FAILURE;
        }

        $data = $response->toArray();

        if (!isset($data['current']['air_quality']['pm2_5'])) {
            $output->writeln('Air quality data is not available for the specified location.');
            return Command::FAILURE;
        }

        $pm25Index = (float) $data['current']['air_quality']['pm2_5'];

        // Determine emoji and color based on PM2.5 value
        $emoji = '';
        $color = '';

        if ($pm25Index >= 0 && $pm25Index <= 12.0) {
            $emoji = '😃';
            $color = 'green';
        } elseif ($pm25Index >= 12.1 && $pm25Index <= 35.4) {
            $emoji = '😐';
            $color = 'yellow';
        } elseif ($pm25Index >= 35.5 && $pm25Index <= 55.4) {
            $emoji = '😷';
            $color = 'orange';
        } elseif ($pm25Index >= 55.5 && $pm25Index <= 150.4) {
            $emoji = '😨';
            $color = 'red';
        } elseif ($pm25Index >= 150.5 && $pm25Index <= 250.4) {
            $emoji = '😷';
            $color = 'red';
        } elseif ($pm25Index >= 250.5) {
            $emoji = '🚫';
            $color = 'red';
        }

        $output->writeln("<fg={$color}>{$emoji} Current Air Quality (PM2.5): $pm25Index</>");

        return Command::SUCCESS;
    }
}