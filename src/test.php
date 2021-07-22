<?php

// Load all Composer packages
require __DIR__ . '/../vendor/autoload.php';

use \Achetronic\LetsHaproxy\Controllers\HaproxyController;

$configPath = "./haproxy.cfg";
$output = HaproxyController::filterConfig($configPath, "frontend");

var_dump($output);