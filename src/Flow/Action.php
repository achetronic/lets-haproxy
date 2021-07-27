<?php

namespace Achetronic\LetsHaproxy\Flow;

use \Achetronic\LetsHaproxy\Haproxy\Config;
use \Achetronic\LetsHaproxy\Haproxy\Service;
use \Achetronic\LetsHaproxy\Certbot\Certificate;

final class Action
{
    public static function createCerts()
    {
        $certificate = new Certificate("test@test.com");
        if(!$certificate->createSingleCerts()){
            echo "Creation command";
            exit(1);
        }
    }
}