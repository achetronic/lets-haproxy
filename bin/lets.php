#!/usr/bin/env php
<?php

use Achetronic\LetsHaproxy\Console\Command;
use Achetronic\LetsHaproxy\Flow\Action;
use Achetronic\LetsHaproxy\Haproxy\Service;

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

Command::showLogo();

Command::match(['create'], function(){
    Action::createCerts();
});

Command::match(['renew'], function(){
    Action::renewCerts();
});

Command::match(['watch'], function(){
    Action::watchLogs();
});

Command::match(['log:success'], function(){
    Action::logSuccess();
});

Command::match(['log:error'], function(){
    Action::logError();
});

Command::match(['-h', 'help', '--help'], function(){
    Command::showHelp();
});

Command::match(['start'], function(){
    Service::start();
});

Command::match(['stop'], function(){
    Service::stop();
});

Command::match(['restart'], function(){
    Service::restart();
});

Command::showHelp();