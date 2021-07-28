<?php

namespace Achetronic\LetsHaproxy\Flow;

use \Achetronic\LetsHaproxy\Haproxy\Service;
use \Achetronic\LetsHaproxy\Certbot\Certificate;
use \Achetronic\LetsHaproxy\Console\Show;
use \Achetronic\LetsHaproxy\Console\Log;

final class Action
{
    /**
     * Create certificates, prepare them
     * and configure Haproxy for using them
     *
     * @return void
     */
    public static function createCerts() :void
    {
        Log::info("Configuring required parameters");
        if( !($email = getenv("EMAIL")) || !($environment = getenv("ENVIRONMENT")) ){
            Log::error("Failed getting parameters from env vars");
            exit(1);
        }

        if(!Certificate::setParameters($email, $environment)){
            Log::error("Failed setting required parameters");
            exit(1);
        }

        Log::info("Creating certificates");
        if(!Certificate::createSingles()){
            Log::error("Failed creating certificates");
            exit(1);
        }

        Log::info("Preparing certificates for Haproxy");
        if(!Certificate::mergePems()){
            Log::error("Failed merging certificates");
            exit(1);
        }

        Log::info("Restarting Haproxy");
        Service::restart();

        Log::success("Everything done. Enjoy your proxy ;)");
        // echo Log::getColored("Everything done. Enjoy your proxy ;)", "green").PHP_EOL;
        exit(0);
    }

    /**
     * Renew certificates and prepare them
     * to be used by Haproxy
     *
     * @return void
     */
    public static function renewCerts()
    {
        Log::info("Renewing certificates");
        if(!Certificate::renewAll()){
            Log::error("Failed renewing certificates");
            exit(1);
        }

        Log::info("Preparing certificates for Haproxy");
        if(!Certificate::mergePems()){
            Log::error("Failed merging certificates");
            exit(1);
        }

        Log::info("Restarting Haproxy");
        Service::restart();

        Log::success("Everything done. Enjoy your proxy ;)");
        // echo Log::getColored("Everything done. Enjoy your proxy ;)", "green").PHP_EOL;
        exit(0);
    }

    /**
     * Watch the logfile
     *
     * @return void
     */
    public static function watchLogs()
    {
        Show::info("Watching logs");

        if(!file_exists(Log::LOG_PATH))
            @file_put_contents(Log::LOG_PATH, '[---]'.PHP_EOL);

        $lastLine = '';
        while(true){
            $line = Log::getLast();
            if(!$line){
                Log::error("Impossible to read last log");
                break;
            }

            if($line !== $lastLine){
                $lastLine=$line;
                $filteredLine = preg_replace("/^(\[[0-9\s\-\:]+\]){1}/", '', $line, 1);
                $filteredLine = trim((string)$filteredLine);
                if(!empty($filteredLine))
                    echo $filteredLine.PHP_EOL;
            }
        }

        Show::error("Logs watcher unexpected failure");
        exit(1);
    }

    /**
     *
     *
     * @return void
     */
    public static function logSuccess()
    {
        Log::success("This is a testing action");
    }

    /**
     *
     *
     * @return void
     */
    public static function logError()
    {
        Log::error("This is a testing action");
    }

}