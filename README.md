# SCMS-OAUTH

Brief project description goes here.

## Table of Contents

- [Ubuntu Installation](#ubuntu-installation)
- [Apache2 & PHP Setup](#apache2-php-setup)
- [Mongodb Installation](#mongodb-installation)
- [Project Setup](#project-setup)
- [License](#license)

## Ubuntu Installation

1. Open Microsoft Store and download Ubuntu 20.04.6 LTS.

## Apache2 & PHP Setup

1. Enable PHP Repository:

    ```bash
    sudo apt install software-properties-common
    sudo add-apt-repository ppa:ondrej/php
    sudo apt update    
    ```

2. Install PHP 8:

    ```bash
    sudo apt install php8.0
    ```

3. Installing PHP Extensions:

    ```bash
    sudo apt install php8.0-common php8.0-mysql php8.0-xml php8.0-curl php8.0-gd php8.0-imagick php8.0-cli php8.0-dev php8.0-imap php8.0-mbstring php8.0-opcache php8.0-soap php8.0-zip -y
    ```

4. Install PHP 8 with Apache
    ```bash
    sudo apt install libapache2-mod-php8.0 
    sudo a2enmod php8.0 
    ```

## Mongodb installation

1. Install Mongo DB:

    ```bash
    curl -fsSL https://www.mongodb.org/static/pgp/server-4.4.asc | sudo apt-key add -
    sudo apt update
    sudo apt install mongodb
    ```

2. Install the Laravel MongoDB PHP Extension:

    ```bash
    sudo pecl install mongodb
    sudo nano /etc/php/8.0/cli/php.ini
    *Ctrl W and search 'dynamic extension' and add on new extension call extension=mongodb.so
    service mongodb status
    ```

## Project Setup

1. Install Project

    ```bash
    git clone "git project link"
    composer install
    php artisan serve --host=0.0.0.0 --port=8000
    ```

2. Create an .env file (can copy from .env.example) and modify the DB part. For example:

    ```bash
    DB_CONNECTION=mongodb
    DB_HOST=127.0.0.1
    DB_PORT=27017
    DB_DATABASE=SCMS
    DB_USERNAME=
    DB_PASSWORD=
    ```

3. Run Migrations and Seed Database:
Execute the following commands to set up your database with sample data:

    ```bash
    php artisan migrate:fresh --seed
    ```

4. Start the Development Server:
Launch the development server with the command:

    ```bash
    php artisan serve --host=0.0.0.0 --port=8000
    ```