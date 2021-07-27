#!/usr/bin/env php
<?php

use Achetronic\LetsHaproxy\Flow\Action;

$logo = <<<LOGO
   __      _
  / /  ___| |_ ___     /\  /\__ _ _ __  _ __ _____  ___   _
 / /  / _ \ __/ __|   / /_/ / _` | '_ \| '__/ _ \ \/ / | | |
/ /__|  __/ |_\__ \  / __  / (_| | |_) | | | (_) >  <| |_| |
\____/\___|\__|___/  \/ /_/ \__,_| .__/|_|  \___/_/\_\\__, |
                               |_|                    |___/

LOGO;

echo $logo;

# Review dependencies
if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    echo "You need to install the composer dependencies. Try to execute: composer install --no-dev";
    exit(-1);
}

if (!function_exists('openssl_pkey_get_private')) {
    echo "You need to enable OpenSSL in your php.ini";
    exit(-2);
}

require __DIR__ . '/../vendor/autoload.php';

# Define commands
$commands = [
    'create'   => 'Create certificates',
    'renew'    => 'Renew certificates',
    'version'  => 'Print version information',
    'help'     => 'Print this help information',
];

# Show help messages
$help = implode(PHP_EOL, array_map(function ($command) use ($commands) {
    $help = "\033[0;31m${command}\033[0m"." ➔ ".$commands[$command];
    return $help;
}, array_keys($commands)));

# Execute Action: help
if (count($argv) === 1 || in_array($argv[1], ['-h', 'help', '--help'], true)) {
    print($logo . $help);
    exit(0);
}

# Execute Action: create
if (count($argv) === 1 || in_array($argv[1], ['create'], true)) {
    Action::createCerts();
    exit(0);
}
