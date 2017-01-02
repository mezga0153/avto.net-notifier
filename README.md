# avto.net-notifier

avto.net distcontinued SMS notifications!

This script sends you an email every time a provided search result changes.

Prerequisites
=============

Install php, on ubuntu you can do it like this:

    $ sudo apt-get install php-cli

Install php packages required to run the script:

[install composer](https://getcomposer.org/doc/00-intro.md)

	$ php composer.phar install

Copy `config.json.dist` to `config.json` and fill in the configuration values with your favorite text editor

Running
=======

Run the script with:

    $ php notifier.php
