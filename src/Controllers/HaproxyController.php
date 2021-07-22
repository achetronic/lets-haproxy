<?php

namespace Achetronic\LetsHaproxy\Controllers;

/**
 * This class has all needed functions
 * to
 *
 *
 */
class HaproxyController
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

    /**
     * Convert Haproxy config file
     * into an array
     *
     * @param string $configPath
     * @return array
     */
    public static function parseConfig(string $configPath) : array
    {
        $currentSection = [];
        $sections = [];

        $filePointer = @fopen($configPath, "r");
        if(!$filePointer){ return []; }

        while(($line = fgets($filePointer)) !== false){
            if(strpos( $line , "#" ) === 0) continue;
            $line = preg_replace('/\s+/', ' ',$line);

            $brokenLine = explode(" ", trim($line), 2);
            $brokenLine[0] = trim($brokenLine[0]);
            $brokenLine[1] = isset($brokenLine[1]) ? trim($brokenLine[1]) : null;

            # Section found
            if(in_array($brokenLine[0], self::CONFIG_RESERVED_KEYWORDS)){
                if (isset($currentSection["type"])){
                    $sections[$currentSection["type"]][] = $currentSection;
                    $currentSection = [];
                }
                $currentSection["type"] = $brokenLine[0];

                $currentSection["name"] = null;
                if($brokenLine[0] !== "defaults" && $brokenLine[0] !== "global")
                    $currentSection["name"] = $brokenLine[1];
            }

            # Command found
            if(!in_array($brokenLine[0], self::CONFIG_RESERVED_KEYWORDS)){
                count($brokenLine)>1
                    ? $currentSection[$brokenLine[0]][] = $brokenLine[1]
                    : $currentSection[$brokenLine[0]] = null;
            }
        }
        if (feof($filePointer) && !empty($currentSection)) {
            $sections[$currentSection["type"]][] = $currentSection;
        }
        fclose($filePointer);
        return $sections;
    }

    /**
     * Return desired section defined
     * into Haproxy config file
     *
     * @param string $configPath
     * @param string $section
     * @return array
     */
    public static function filterConfig(string $configPath, string $section): array
    {
        if(!in_array($section, self::CONFIG_RESERVED_KEYWORDS)) return [];

        $config = self::parseConfig($configPath);

        if(!array_key_exists($section, $config)) return [];
        return $config[$section];
    }











    /**
     * Reconfigure Haproxy to
     * run just as certbot proxy
     *
     * @return bool
     */
    // public function ParseUserTemplate () : bool
    // {
    //     try {

    //         $parser = new Parser($this->templateUserFile);

    //         # Parse the configuration
    //         $configuration = $parser->parse();

    //         # Store https (443 binded) frontend into a var
    //         $httpsFrontend = null;

    //         # Loop over all the frontends looking for one binded to 443
    //         foreach ( $configuration->getFrontendSections() as $index => &$frontendSection) {

    //             # Look for bindings
    //             $bindValue = $frontendSection->getParameterByName('bind')->getValue();

    //             # Get only port numbers
    //             $hasPort = preg_match('/([0-9]{1,5}){1}/', $bindValue, $parsedPort);
    //             $parsedPort = $parsedPort[0];

    //             # Get only frontend with ports 443
    //             if( !empty($parsedPort) && intval($parsedPort) === 443 ){
    //                 $httpsFrontend = $frontendSection;
    //                 #unset($frontendSection);
    //             }
    //         }

    //         # Check for saving https section successfully
    //         if ( empty($httpsFrontend) ) {
    //             throw new Exception();
    //         }

    //         # Loop over parameters looking for domains and changing parameters
    //         $domainsToCert = [];
    //         foreach ( $httpsFrontend->getParameters() as $index => &$item) {

    //             # Get the parameter
    //             $parameterName  = $item->getName();
    //             $parameterValue = $item->getValue();

    //             # Touch bind parameter to add certificates
    //             if ( $item->getName() == 'bind' ){
    //                 $item->setValue('*:443 ssl crt /etc/letsencrypt/haproxy/');
    //             }

    //             # Look into ACL for domains
    //             if ( $item->getName() == 'acl' ){

    //                 # Extract the domain
    //                 $hasDomain = preg_match('/(-i\s+){1}(([a-z0-9]+(-[a-z0-9]+)*\.)+[a-z]{2,})/', $item->getValue(), $parsedDomain);

    //                 # Add the domain to the list
    //                 if( $hasDomain ){
    //                     $domainsToCert[] = $parsedDomain[2];
    //                 }
    //             }
    //         }

    //         # Add modified section to the config
    //         #$configuration->addSection( $httpsFrontend );

    //         # Dump the config into a temporary file and check it
    //         @unlink('/tmp/haproxy.cfg');
    //         $writer = new Writer($configuration);
    //         if( !file_put_contents('/tmp/haproxy.cfg', $writer->dump()) ){
    //             throw new Exception('temporary file could not be stored');
    //         }

    //         # Save domains to get certs
    //         $this->domainsToCert = $domainsToCert;

    //         return true;

    //     } catch ( Exception $e ) {
    //         return false;
    //     }
    // }




}