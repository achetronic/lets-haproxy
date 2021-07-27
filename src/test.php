<?php

// Load all Composer packages
require __DIR__ . '/../vendor/autoload.php';

use \Achetronic\LetsHaproxy\Haproxy\Config;
use \Achetronic\LetsHaproxy\Haproxy\Service;

$configPath = "./haproxy.cfg";

Config::parse($configPath);
Config::prepare();
Config::store("test.cfg");

var_dump(Config::dump());



//$config->store("test.cfg");

// class a {
//     public static $a = "hola";
//     public $b = "hola";

//     public static function despedida ()
//     {
//         self::$a = "adios";
//     }
//     public function despedidanoestatic ()
//     {
//         $this->b = "adios";
//     }
// }
// $var = new a();
// $foo = new a();
// echo a::$a . PHP_EOL;
// echo $var->b . PHP_EOL;
// echo $foo->b . PHP_EOL;
// a::despedida();
// $var->despedidanoestatic();
// echo a::$a. PHP_EOL;
// echo $var->b. PHP_EOL;
// echo $foo->b. PHP_EOL;