<?php

namespace Achetronic\LetsHaproxy\Haproxy;

/**
 * This class has all needed functions
 * to
 *
 *
 */
final class Config
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
     * Haproxy file converted
     * into an array
     *
     * @var array
     */
    private $parsedConfig;

    /**
     * Convert Haproxy config file
     * into an array
     *
     * @param string $configPath
     * @return array
     */
    private static function parseConfig(string $configPath) :array
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
     * Parse Haproxy config file
     * and store it into the instance
     *
     * @param string $configPath
     * @return bool
     */
    public function parse(string $configPath) :bool
    {
        $parsedConfig = self::parseConfig($configPath);

        if(empty($parsedConfig)){
            return false;
        }
        $this->parsedConfig = $parsedConfig;
        return true;
    }

    /**
     * Return desired section
     *
     * @param string $section
     * @return array
     */
    public function getSection(string $section) :array
    {
        if(!in_array($section, self::CONFIG_RESERVED_KEYWORDS)) return [];

        if(!array_key_exists($section, $this->parsedConfig)) return [];

        return $this->parsedConfig[$section];
    }

    /**
     * Return desired section
     * defined by its name
     *
     * @param string $section
     * @param string $name
     * @return array
     */
    public function getSectionByName(string $section, string $name) :array
    {
        $section = $this->getSection($section);
        if(empty($section)) return [];

        if($section === "defaults" || $section === "global" )
            return $section;

        foreach($section as $key => $item){
            if(array_key_exists("name", $item) && $item["name"] === $name)
                return $item;
        }
        return [];
    }

    /**
     * Return frontends
     * binded to 443 port
     *
     * NOTE: Should be one
     *
     * @return array
     */
    public function getSecureFrontends() :array
    {
        $frontends = $this->getSection("frontend");
        $secureFrontends = [];

        foreach($frontends as $key => $frontend){
            if(!array_key_exists("bind", $frontend)) continue;

            foreach ($frontend["bind"] as $bind){
                if(!preg_match('/(:443){1}/', $bind)) continue;
                $secureFrontends[] = $frontend;
                break;
            }
        }
        return $secureFrontends;
    }

    /**
     * Return domains linked to
     * frontends binded to 443 port
     *
     * @return array
     */
    public function getSecureDomains() :array
    {
        $frontends = $this->getSecureFrontends();
        $secureDomains = [];

        foreach($frontends as $key => $frontend){
            if(!array_key_exists("acl", $frontend)) continue;

            foreach ($frontend["acl"] as $acl){
                $hasDomain = preg_match('/(-i\s+){1}(([a-z0-9]+(-[a-z0-9]+)*\.)+[a-z]{2,})/', $acl, $parsedDomain);

                if(!$hasDomain) continue;
                $secureDomains[] = $parsedDomain[2];
            }
        }
        return $secureDomains;
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