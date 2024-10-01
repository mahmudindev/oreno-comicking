# OrenoComic

PHP Symfony-based comic catalog full-stack.

## Requirements

- PHP 8.2 or higher.
- MariaDB 10.11 or higher.

## Usage

Before proceeding check .env file and follow the instructions to configure the application. There are 2 different ways of running this application depending on your needs:

1. **Symfony CLI**

    [Download Symfony CLI](https://symfony.com/download) and run this command:

    ```bash
    symfony serve
    ```

    Then access the application in your browser at the given URL (<https://localhost:8000> by default).

2. **Web Server**

    Use a compatible web server like Nginx or Apache to run the application. Read the documentation about [configuring a web server for Symfony](https://symfony.com/doc/current/setup/web_server_configuration.html).

    Or on your local machine, you can run this command to use the built-in PHP web server:

    ```bash
    php -S localhost:8000 -t public/
    ```
