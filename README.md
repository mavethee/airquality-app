# Air Quality App

This is a console application written in PHP that allows you to check the air quality for specific locations.

## Installation

```sh
# Clone the repository
git clone https://github.com/mavethee/airquality-app.git

# Install Composer (if not already installed)
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php -r "if (hash_file('sha384', 'composer-setup.php') === 'e21205b207c3ff031906575712edab6f13eb0b361f2085f1f1237b7126d785e826a450292b6cfd1d64d92e6563bbde02') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
php composer-setup.php
php -r "unlink('composer-setup.php');"
sudo mv composer.phar /usr/local/bin/composer

# Install the required dependencies using Composer
composer install

```

## Usage

Navigate to the cloned repository:

```sh
cd airquality-app
```

Run the application to check air quality for a specific city (replace `(city name)` with the desired city):

```sh
php bin/console air-quality (city name)
```

For example, to check air quality in London:

```sh
php bin/console air-quality london
```

You can also include the `--history` option to retrieve historical air quality data (replace `(city name)` with the desired city):

```sh
php bin/console air-quality --history (city name)
```

For example, to retrieve air quality data for London saved locally in JSON file:

```sh
php bin/console air-quality --history london
```

## Note:

After switching from trial to free plan, it's not working as intended when comes to checking Air Quality for the next day, that's to fix for later.

**API key is part of the source code as this is a sample project, never do that in case of important projects**