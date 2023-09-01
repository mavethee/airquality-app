# Air-Quality-App
 Console application to check air quality for certain locations written in PHP.

## Installation
1. Clone the repository:
    ```sh
    git clone https://github.com/mavethee/airquality-app.git
    ```
2. Install composer:
    ```sh
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" # Get setup

    php -r "if (hash_file('sha384', 'composer-setup.php') === 'e21205b207c3ff031906575712edab6f13eb0b361f2085f1f1237b7126d785e826a450292b6cfd1d64d92e6563bbde02') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;" # Verify the installer

    php composer-setup.php # Run the installer

    php -r "unlink('composer-setup.php');" # Delete the installer

    sudo mv composer.phar /usr/local/bin/composer # Most likely, you want to put the composer.phar into a directory on your PATH, so you can simply call composer from any directory

    ```

3. Install neeeded dependencies with composer:

    ```sh
    composer install
    ```


## Usage:

1. Locate cloned repo.

2. Run the application:
    ```sh
    php bin/console air-quality [city name]
    ```

    like example usage above to check air quality in London:

    ```sh
    php bin/console air-quality london
    ```


