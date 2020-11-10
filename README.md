# ApiRestLohgin

This project was created with Symfony 5.1

## Installation

# Install the libraries with the command:

`composer install`

# Customize those lines in your .env!

`DATABASE_URL="mysql://db_user:db_password@127.0.0.1:3306/db_name?serverVersion=5.7"`

`MAILER_DSN=smtp://user:password@smtp:587`

# Create database with the following command:

`php bin/console doctrine:database:create`

# Make the migrations with the commands:

`php bin/console make:migration`

## Running the project

`php bin/console server:run`
