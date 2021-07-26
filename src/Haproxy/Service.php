<?php

namespace Achetronic\LetsHaproxy\Haproxy;

final class Service
{
    /**
     * Stop Haproxy service
     *
     * @return void
     */
    public static function start() :void
    {
        $cmd = shell_exec('service haproxy start');
    }

    /**
     * Stop Haproxy service
     *
     * @return void
     */
    public static function stop() :void
    {
        $cmd = shell_exec('service haproxy stop');
    }

    /**
     * Restart Haproxy service
     *
     * @return void
     */
    public static function restart() :void
    {
        # Restart Haproxy
        $cmd = shell_exec('service haproxy stop');
        $cmd = shell_exec('service haproxy start');
    }
}
