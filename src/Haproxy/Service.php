<?php

namespace Achetronic\LetsHaproxy\Haproxy;

/**
 * This class has all needed functions
 * to
 *
 *
 */
final class Service
{
    /**
     * Path to the haproxy.cfg
     * file suitable for certbot
     *
     * @var string
     */
    public $templateCertbotFile = null;

    /**
     * Path to the user's haproxy.cfg
     * file to parse
     *
     * @var string
     */
    public $templateUserFile = null;

    /**
     * Path to the haproxy.cfg file
     *
     * @var string
     */
    public $configFile = null;

    /**
     * List of domains to get
     * certs for
     *
     * @var array
     */
    public $domainsToCert = null;

    /**
     *
     *
     *
     */
    public function __construct ()
    {
        $this->templateCertbotFile = "/root/templates/haproxy.certbot.cfg";
        $this->templateUserFile    = "/root/templates/haproxy.user.cfg";
        $this->configFile          = "/etc/haproxy/haproxy.cfg";
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
     * Stop Haproxy service
     *
     * @return void
     */
    public static function stop() :void
    {
        $cmd = shell_exec('service haproxy stop');
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
     * Reconfigure Haproxy to
     * run just as certbot proxy
     *
     * @return bool
     */
    public function setCertbotConfig() :bool
    {
        try {
            if( !copy($this->templateCertbotFile, $this->configFile) )
                throw new Exception ("Failed to move Certbot config template to destination");

            return true;
        } catch ( Exception $e ) {
            return false;
        }
    }

    /**
     * Reconfigure Haproxy to
     * work as a production proxy
     *
     * @return bool
     */
    public function setRegularConfig() :bool
    {
        try {
            if(!$this->parseUserTemplate())
                throw new Exception ("Failed to parse user's config file");

            if( !copy('/tmp/haproxy.cfg', $this->configFile) )
                throw new Exception ("Failed to move /tmp/haproxy.cfg to destination");

            return true;
        } catch ( Exception $e ) {
            return false;
        }
    }
}
