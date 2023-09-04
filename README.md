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
```sh
# Navigate to the cloned repository
cd airquality-app
```

# Run the application to check air quality for a specific city (replace [city name] with the desired city)


For example, to check air quality in London:
```sh
php bin/console air-quality london
```



