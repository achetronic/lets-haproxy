<?php

namespace Achetronic\LetsHaproxy\Haproxy;

/**
 * This class has all needed functions
 * to
 *
 *
 */
class Service
{
    /**
     * List of reserved keywords
     * in HAProxy config files
     *
     * REF: https://haproxy.com/blog/the-four-essential-sections-of-an-haproxy-configuration/#the-format
     *
     * @var array
     */
    private const CONFIG_RESERVED_KEYWORDS = [
        "global",
        "defaults",
        "frontend",
        "backend",
        "listen"
    ];

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
    public static function restart()
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
    public static function stop()
    {
        $cmd = shell_exec('service haproxy stop');
    }

    /**
     * Stop Haproxy service
     *
     * @return void
     */
    public static function start()
    {
        $cmd = shell_exec('service haproxy start');
    }

    /**
     * Reconfigure Haproxy to
     * run just as certbot proxy
     *
     * @return bool
     */
    public function setCertbotConfig() : bool
    {
        try {
            if( !copy($this->templateCertbotFile, $this->configFile) ) {
                throw new Exception ("Failed to move Certbot config template to destination");
            }

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
    public function setRegularConfig() : bool
    {
        try {
            if(!$this->parseUserTemplate()) {
                throw new Exception ("Failed to parse user's config file");
            }

            if( !copy('/tmp/haproxy.cfg', $this->configFile) ) {
                throw new Exception ("Failed to move /tmp/haproxy.cfg to destination");
            }

            return true;

        } catch ( Exception $e ) {
            return false;
        }
    }
}
