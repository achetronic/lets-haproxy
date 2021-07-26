<?php

// Load all Composer packages
require __DIR__ . '/../vendor/autoload.php';

use \Achetronic\LetsHaproxy\Haproxy\Config;
use \Achetronic\LetsHaproxy\Haproxy\Service;

$configPath = "./haproxy.cfg";
$config = new Config();

$config->parse($configPath);

$config->prepare();

var_dump($config->dump());
$config->store("test.cfg");
