<?php

// Load all Composer packages
require __DIR__ . '/../vendor/autoload.php';

use \Achetronic\LetsHaproxy\Haproxy\Config;
use \Achetronic\LetsHaproxy\Haproxy\Service;

$configPath = "./haproxy.cfg";
$config = new Config();
$config->parse($configPath);


#var_dump($config->parsedConfig);
//$t = &$config->getSection("global");
$config->prepareSecureFrontends();
echo PHP_EOL.PHP_EOL;
var_dump($config->parsedConfig);

//var_dump($config->getSection("frontend")[0]);



//$output = HaproxyController::filterConfig($configPath, "frontend");

//var_dump($output);