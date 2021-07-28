<?php

namespace Achetronic\LetsHaproxy\Console;

use \Achetronic\LetsHaproxy\Console\Show;

final class Log
{
    /**
     * Path to the config template
     * used by Haproxy on regular
     * requests as proxy
     *
     * @var string
     */
    public const LOG_PATH = "/tmp/lets-haproxy.log";

    /**
     * Get a message tagged
     * and colored in red
     *
     * @return void
     */
    public static function error(string $message, bool $show=true) :void
    {
        $message = "[ERROR] ".$message;
        self::store($message);

        if($show) Show::error($message);
    }

    /**
     * Get a message tagged
     * and colored in yellow
     *
     * @return void
     */
    public static function info(string $message, bool $show=true) :void
    {
        $message = "[INFO] ".$message;
        self::store($message);

        if($show) Show::info($message);
    }

    /**
     * Get a message tagged
     * and colored in green
     *
     * @return void
     */
    public static function success(string $message, bool $show=true) :void
    {
        $message = "[SUCCESS] ".$message;
        self::store($message);

        if($show) Show::success($message);
    }

    /**
     * Store a message
     * in the log file
     *
     * @return bool
     */
    public static function store(string $message) :bool
    {
        $date = date("Y-m-d H:i:s");
        $message = "[${date}] ${message}".PHP_EOL;
        if( !file_put_contents(self::LOG_PATH, $message, FILE_APPEND | LOCK_EX))
            return false;
        return true;
    }

    /**
     *
     *
     * @return string
     */
    public static function getLast() :string|bool
    {
        $fp = @fopen(self::LOG_PATH, 'r');
        if (!$fp) return false;

        $begining = fseek($fp, 0);
        $pos = -2;
        $line = " ";
        while ($line != "\n") {
                fseek($fp, $pos, SEEK_END);
                if(ftell($fp) == $begining){
                break;
                }
                $line = fgetc($fp);
                $pos = $pos - 1;
        }
        $line = fgets($fp);
        fclose($fp);
        return trim($line);
    }
}
