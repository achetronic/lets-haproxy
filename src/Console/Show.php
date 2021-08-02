<?php

namespace Achetronic\LetsHaproxy\Console;

final class Show
{
    /**
     * Available CLI
     * font color codes
     *
     * @var array
     */
    private const CLI_FONT_COLORS = [
        "black"    => "\033[0;30m",
        "red"      => "\033[0;31m",
        "green"    => "\033[0;32m",
        "yellow"   => "\033[0;33m",
        "blue"     => "\033[0;34m",
        "magenta"  => "\033[0;35m",
        "cyan"     => "\033[0;36m",
        "white"    => "\033[0;37m",
        "reset"    => "\033[0m",
    ];

    /**
     * Get a message colored
     * with selected color
     *
     * @return string
     */
    public static function getColored(string $message, string $color="reset") :string
    {
        (string)$line=null;
        if (!in_array(self::CLI_FONT_COLORS, [$color], true)) {
            $line .= self::CLI_FONT_COLORS["reset"];
        }

        $line .= self::CLI_FONT_COLORS[$color].$message.self::CLI_FONT_COLORS["reset"];
        return $line;
    }

    /**
     * Get a message tagged
     * and colored in red
     *
     * @return void
     */
    public static function error(string $message) :void
    {
        echo self::getColored($message, "red").PHP_EOL;
    }

    /**
     * Get a message tagged
     * and colored in yellow
     *
     * @return void
     */
    public static function info(string $message) :void
    {
        echo self::getColored($message, "yellow").PHP_EOL;
    }

    /**
     * Get a message tagged
     * and colored in green
     *
     * @return void
     */
    public static function success(string $message) :void
    {
        echo self::getColored($message, "green").PHP_EOL;
    }
}
