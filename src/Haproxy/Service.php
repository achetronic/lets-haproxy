<?php

namespace Achetronic\LetsHaproxy\Haproxy;

final class Service
{
    function __construct()
    {
        # Parse user configuration
        Config::parse(Config::USER_TEMPLATE_PATH);
        Config::prepare();
    }

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

    /**
     * Change Haproxy configuration to
     * handle Certbot flow
     *
     * @var bool
     */
    public static function setCertbotConfig() :bool
    {
        if( !@copy(Config::CERTBOT_TEMPLATE_PATH, Config::CONFIG_PATH) )
            return false;
        return true;
    }

    /**
     * Change Haproxy configuration to
     * handle user requests
     *
     * @var bool
     */
    public static function setRegularConfig() :bool
    {
        if( !Config::store( Config::TEMP_CONFIG_PATH ) )
            return false;
        return true;
    }

    /**
     * Change mode between certification flow
     * or production
     *
     * @var bool
     */
    public static function changeMode(?string $mode=null) :bool
    {
        if($mode==="certbot"){
            if(!self::setCertbotConfig())
                return false;
        }

        if(!self::setRegularConfig())
            return false;

        self::restart();
        return true;
    }
}
