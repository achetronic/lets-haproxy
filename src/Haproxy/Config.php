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
     * Path to the auto-generated
     * Haproxy config file
     *
     * @var string
     */
    public const TEMP_CONFIG_PATH = "/tmp/haproxy.cfg";

    /**
     * Path to the real
     * Haproxy config file
     *
     * @var string
     */
    public const CONFIG_PATH = "/etc/haproxy/haproxy.cfg";

    /**
     * Path to the config template
     * used by Haproxy on Lets Encrypt
     * challenge requests
     *
     * @var string
     */
    public const CERTBOT_TEMPLATE_PATH = "/usr/src/app/templates/haproxy.certbot.cfg";

    /**
     * Path to the config template
     * used by Haproxy on regular
     * requests as proxy
     *
     * @var string
     */
    public const USER_TEMPLATE_PATH = "/usr/src/app/templates/haproxy.user.cfg";

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
    public static $parsedConfig = [];

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
            $line = preg_replace('/\s+/', ' ', trim($line));
            if(empty($line)) continue;
            if(strpos( $line , "#" ) === 0) continue;

            $brokenLine = explode(" ", $line, 2);
            $brokenLine[0] = trim($brokenLine[0]);
            $brokenLine[1] = isset($brokenLine[1]) ? trim($brokenLine[1]) : null;

            # Section found
            if(in_array($brokenLine[0], self::CONFIG_RESERVED_KEYWORDS)){
                if (isset($currentSection["type"])){
                    $sections[] = $currentSection;
                    $currentSection = [];
                }
                $currentSection["type"] = $brokenLine[0];

                $currentSection["label"] = null;
                if($brokenLine[0] !== "defaults" && $brokenLine[0] !== "global")
                    $currentSection["label"] = $brokenLine[1];
            }

            # Command found
            if(!in_array($brokenLine[0], self::CONFIG_RESERVED_KEYWORDS)){
                count($brokenLine)>1
                    ? $currentSection[$brokenLine[0]][] = $brokenLine[1]
                    : $currentSection[$brokenLine[0]] = null;
            }
        }
        if (feof($filePointer) && !empty($currentSection)) {
            $sections[] = $currentSection;
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
    public static function parse(string $configPath) :bool
    {
        $parsedConfig = self::parseConfig($configPath);

        if(empty($parsedConfig)){
            return false;
        }

        self::$parsedConfig = $parsedConfig;
        return true;
    }

    /**
     * Get parsed config stored
     * inside the instance
     *
     * @return array
     */
    public static function getParsed() :array
    {
        if(empty(self::$parsedConfig)) return [];
        return self::$parsedConfig;
    }

    /**
     * Return a pointer array
     * to all section whose type is $type
     *
     * @param string $type
     * @return array
     */
    private static function getSection(string $type) :array
    {
        if(!in_array($type, self::CONFIG_RESERVED_KEYWORDS)) return [];

        $results = [];
        foreach(self::$parsedConfig as $key => $section){
            if($section["type"] === $type) $results[$key]=$section;
        }

        return $results;
    }

    /**
     * Return desired section
     * defined by its label
     *
     * @param string $type
     * @param string $label
     * @return array
     */
    private static function getSectionByName(string $type, ?string $label=null) :array
    {
        $section = self::getSection($type);
        if(empty($section)) return [];

        if($type === "defaults" || $type === "global" )
            return $section;

        foreach($section as $key => $item){
            if(array_key_exists("label", $item) && $item["label"] === $label)
                return [$key => $item];
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
    public static function getSecureFrontends() :array
    {
        $frontends = self::getSection("frontend");
        $secureFrontends = [];

        foreach($frontends as $key => $frontend){
            if(!array_key_exists("bind", $frontend)) continue;

            foreach ($frontend["bind"] as $bind){
                if(!preg_match('/(:443){1}/', $bind)) continue;
                $secureFrontends[$key] = $frontend;
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
    public static function getSecureDomains() :array
    {
        $frontends = self::getSecureFrontends();
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
     * Prepare frontends binded to 443 port
     * to use Let's Encrypt certs
     *
     * @return bool
     */
    private static function prepareSecureFrontends() :bool
    {
        $secureFrontends = self::getSecureFrontends();
        $preparedFrontends = [];

        foreach($secureFrontends as $key => $frontend){

            foreach ($frontend["bind"] as &$value){
                if(!preg_match('/(:443){1}/', $value)) continue;
                $value = preg_replace('/(:443){1}/', '${1} ssl crt /etc/letsencrypt/haproxy/', $value);
            }
            $preparedFrontends[$key] = $frontend;
        }
        $replace = array_replace(self::$parsedConfig, $preparedFrontends);
        if(is_null($replace)) return false;

        self::$parsedConfig = $replace;
        return true;
    }

    /**
     * Prepare config to use Let's Encrypt certs
     *
     * @return bool
     */
    public static function prepare() :bool
    {
        if(!self::prepareSecureFrontends())
            return false;
        return true;
    }

    /**
     * Dump all the stored config
     * ready to be stored
     *
     * NOTE: DO IT BETTER, MAN
     *
     * @return string
     */
    public static function dump() :string
    {
        (string)$content=null;
        foreach(self::$parsedConfig as $key => $section){
            $content .= $section["type"] . " " . $section["label"].PHP_EOL;
            foreach($section as $parameter => $values){
                if($parameter == "type" || $parameter== "label") continue;
                foreach($values as $value){
                    $content .= "  ".$parameter." ".$value.PHP_EOL;
                }
            }
        }
        return (string)$content;
    }

    /**
     * Store the config into a file
     *
     * @param string $configPath
     * @return bool
     */
    public static function store(string $configPath) :bool
    {
        if(!@file_put_contents($configPath, self::dump())){
            return false;
        }
        return true;
    }
}
