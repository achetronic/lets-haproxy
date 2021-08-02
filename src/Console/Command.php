<?php

namespace Achetronic\LetsHaproxy\Console;

use Achetronic\LetsHaproxy\Console\Log;

final class Command
{
    /**
     * Available CLI commands
     * and their descriptions
     *
     * @var array
     */
    private const COMMANDS = [
        "create"        => "Create certificates",
        "renew"         => "Renew certificates",
        "watch"         => "Loop watching logs",
        "log:success"   => "Throw exit code 0",
        "log:error"     => "Throw exit code 1",
        "help"          => "Print this help information",
        "start"         => "Start Haproxy",
        "stop"          => "Stop Haproxy",
        "restart"       => "Restart Haproxy",
    ];

    /**
     * Print the CLI logo
     *
     * @return void
     */
    public static function showLogo() :void
    {
        $logo = <<<LOGO
           __      _
          / /  ___| |_ ___     /\  /\__ _ _ __  _ __ _____  ___   _
         / /  / _ \ __/ __|   / /_/ / _` | '_ \| '__/ _ \ \/ / | | |
        / /__|  __/ |_\__ \  / __  / (_| | |_) | | | (_) >  <| |_| |
        \____/\___|\__|___/  \/ /_/ \__,_| .__/|_|  \___/_/\_\\__, |
                                         |_|                  |___/
        LOGO;
        echo $logo.PHP_EOL;
    }

    /**
     * Print a message with
     * available CLI commands
     *
     * @return void
     */
    public static function showHelp() :void
    {
        # Show help messages
        echo PHP_EOL;
        echo Show::getColored("Available commands are explained in the following help:").PHP_EOL;

        $help = implode(PHP_EOL, array_map(function ($command) {
            $line  = Show::getColored($command, "red");
            $line .= Show::getColored(" ➔ ", "yellow");
            $line .= Show::getColored(self::COMMANDS[$command]);
            return $line;
        }, array_keys(self::COMMANDS)));

        echo $help.PHP_EOL;
    }

    /**
     * Execute a callback if a command
     * matches the available CLI commands
     *
     * @return void
     */
    public static function match(array $command, callable $callback) :void
    {
        global $argc, $argv;

        if ($argc >= 2 && in_array($argv[1], $command, true)) {
            $callback();
            exit(0);
        }
    }
}
